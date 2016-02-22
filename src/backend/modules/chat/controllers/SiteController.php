<?php
namespace backend\modules\chat\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\utils\TimeUtil;
use backend\models\Token;
use backend\models\Validation;
use backend\modules\helpdesk\models\ChatConversation;
use backend\modules\helpdesk\models\HelpDesk;
use backend\utils\UrlUtil;
use backend\models\Message;

class SiteController extends Controller
{
    /**
     * Login
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/chat/site/login<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the help desk to login.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     email: string, the user email, required<br/>
     *     password: string, the user password, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     msg: string, if create fail, it contains the error message<br/>
     *     data: array, json array to describe the users detail information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "email"    : "harrysun@augmentum.com.cn",
     *     "password" : "abc123"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack'  : 1,
     *    'data' : {
     *        "accessToken" : "7f2d1e92-9629-8429-00be-2d9c6d64acdb",
     *        "userInfo"    : {
     *            "name"   : "harry",
     *            "avatar" : "path/to/avatar"
     *        }
     *    }
     * }
     * </pre>
     */
    public function actionLogin()
    {
        $params = $this->getParams();
        $deviceToken = $this->getParams('deviceToken');
        $environment = $this->getParams('environment');

        if (empty($params['email']) || empty($params['password'])) {
            throw new BadRequestHttpException("parameters missing");
        }

        $helpdesk = HelpDesk::getByEmail($params['email']);

        if (empty($helpdesk)) {
            throw new ForbiddenHttpException("用户不存在");
        }

        if (!$helpdesk->isActivated) {
            throw new ForbiddenHttpException("用户未激活,请激活后使用");
        }

        if (!$helpdesk->isEnabled) {
            throw new ForbiddenHttpException("该账号已被禁用,请与管理员联系");
        }

        if ($helpdesk->validatePassword($params['password'])) {
            $tokens = Token::getUnexpiredByUserId($helpdesk->_id);
            if (!empty($tokens)) {
                $data = ['isForcedOffline' => true, 'id' => $helpdesk->_id . ''];
                $accountId = $tokens[0]->accountId;
                Yii::$app->tuisongbao->triggerEvent(ChatConversation::EVENT_FORCED_OFFLINE, $data, [ChatConversation::CHANNEL_GLOBAL . $accountId]);

                //deviceToken changed, push forcedOffline
                if ((empty($deviceToken) && !empty($helpdesk->deviceToken)) ||
                    (!empty($deviceToken) && !empty($helpdesk->deviceToken) && $deviceToken != $helpdesk->deviceToken)) {
                    $extra = [
                        'deskId' => $helpdesk->_id . '',
                        'type' => 'forcedOffline',
                        'sentTime' => TimeUtil::msTime()
                    ];
                    $target = [$helpdesk->environment => [$helpdesk->deviceToken]];
                    Yii::$app->tuisongbao->pushMessage($target, 0, $extra, null);
                }

                Token::updateAll(['$set' =>['expireTime' => new \MongoDate()]], ['_id' => ['$in' => Token::getIdList($tokens)]]);
            }
            $isFirstLogin = empty($helpdesk->lastLoginAt);
            $accessToken = Token::createByHelpDesk($helpdesk);
            if (isset($deviceToken)) {
                $helpdesk->loginDevice = HelpDesk::MOBILEAPP;
            } else {
                $helpdesk->loginDevice = HelpDesk::BROWSER;
            }
            $helpdesk->deviceToken = $deviceToken;
            $helpdesk->environment = $environment;
            $helpdesk->lastLoginAt = new \MongoDate();
            $helpdesk->save(true, ['deviceToken', 'loginDevice', 'environment', 'lastLoginAt']);

            $accountId = (string) $helpdesk->accountId;
            $helpdeskId = (string) $helpdesk->_id;
            //Update cache indicate that the helpdesk is online
            HelpDesk::join($helpdeskId, $accountId);

            $userInfo = [
                'badge' => $helpdesk->badge,
                'name' => $helpdesk->name,
                'email' => $helpdesk->email,
                'language' => $helpdesk->language,
                'avatar' => empty($helpdesk->avatar) ? '' : $helpdesk->avatar,
                'id' => $helpdeskId,
                'accountId' => $accountId,
                'notificationType' => $helpdesk->notificationType,
                'isFirstLogin' => $isFirstLogin
            ];

            // Update the status which is customer service.
            Yii::$app->tuisongbao->triggerEvent(HelpDesk::EVENT_ONLINE_STATUS, [], [Message::CHANNEL_GLOBAL . $accountId]);

            return ["accessToken" => $accessToken['accessToken'], 'userInfo' => $userInfo];
        } else {
            throw new ForbiddenHttpException("密码错误");
        }
    }

    /**
     * Logout
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/chat/site/logout
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the user to logout.
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/chat/site/logout?accesstoken=7f2d1e92-9629-8429-00be-2d9c6d64acdb
     * </pre>
     *
     **/
    public function actionLogout()
    {
        $accessToken = $this->getQuery('accesstoken');

        if (empty($accessToken)) {
            return ['token' => $accessToken];
        }

        $token = Token::getToken($accessToken);
        ChatConversation::removeUser((string)$token->userId, (string)$token->accountId, true);
        Token::deleteAll(['accessToken' => $accessToken]);

        // Update the status which is customer service.
        Yii::$app->tuisongbao->triggerEvent(HelpDesk::EVENT_ONLINE_STATUS, [], [Message::CHANNEL_GLOBAL . (string)$token->accountId]);

        return ['token' => $accessToken];
    }

    /**
     * Send Reset password email
     */
    public function actionSendResetPasswordEmail()
    {
        $email = $this->getParams('email');
        $email = mb_strtolower($email);

        if (empty($email)) {
            throw new BadRequestHttpException(Yii::t('chat', 'email_empty'));
        }

        //validate the email
        $user = HelpDesk::getByEmail($email);

        if (empty($user)) {
            throw new BadRequestHttpException(Yii::t('common', 'incorrect_userid'));
        }

        //generate the validation
        $validation = new Validation;
        $validation->userId = $user->_id;
        $validation->expire = new \MongoDate(strtotime('+7 day'));

        if (!$validation->save()) {
            throw new ServerErrorHttpException(Yii::t('chat', 'save_validation_fail'));
        }

        $host = UrlUtil::getDomain();
        $link = $host . '/chat/resetpassword?code=' . $validation->code;
        $mail = Yii::$app->mail;
        $vars = [
            'name' => $user->name,
            'link' => $link,
            'host' => $host
        ];
        $mail->setView('//mail/resetPassword', $vars, '//layouts/email');
        $mail->sendMail($user->email, '群脉重置密码', $user->accountId);
        return ['status' => 'ok'];
    }

    /**
     * Reset password
     */
    public function actionResetPassword()
    {
        $code = $this->getParams('code');
        $newPassword = $this->getParams('password');
        $result = Validation::validateCode($code);

        if ($result == Validation::LINK_INVALID) {
            throw new BadRequestHttpException(Yii::t('common', 'link_invalid'));
        } else if ($result == Validation::LINK_EXPIRED) {
            throw new BadRequestHttpException(Yii::t('common', 'link_expired'));
        }

        $userId = $result;
        $user = HelpDesk::findByPk($userId);

        if (empty($user)) {
            throw new BadRequestHttpException(Yii::t('commmon', 'incorrect_userid'));
        }

        // update the user password
        $user->password = HelpDesk::encryptPassword($newPassword, $user->salt);

        if (!$user->save()) {
            throw new ServerErrorHttpException("Save user failed!");
        }
        Validation::deleteAll(['userId' => $userId]);

        return ['status' => 'ok'];
    }
}
