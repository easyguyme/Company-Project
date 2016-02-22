<?php
namespace backend\modules\chat\controllers;

use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\ChatConversation;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\utils\LogUtil;
use backend\models\Token;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\components\ActiveDataProvider;
use Yii;

/**
 * Help desk controller for common module
 * Update help desk info and password
 */
class HelpDeskController extends RestController
{
    public $modelClass = 'backend\modules\helpdesk\models\HelpDesk';
    const OAUTHURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        $currentHelpdeskId = $this->getUserId();
        $query = HelpDesk::find();

        $clientOpenId = $this->getQuery('clientOpenId');

        if ($orderBy = $this->getQuery('orderBy')) {
            if (StringUtil::isJson($orderBy)) {
                $orderBy = Json::decode($orderBy, true);

                foreach ($orderBy as $key => $value) {
                    if ($value === 'asc' || $value === 'ASC') {
                        $orderBy[$key] = SORT_ASC;
                    } else {
                        $orderBy[$key] = SORT_DESC;
                    }
                }
            } else {
                $orderBy = [$orderBy => SORT_DESC];
            }

            $query->orderBy($orderBy);
        }
        $allHelpdesks = $query->where([
            'accountId' => $accountId,
            'isDeleted' => false,
            'isActivated' => true,
            'isEnabled' => true
        ])->andWhere([
            'not in', '_id', [$currentHelpdeskId]
        ])->orderBy([
            'clientCount' => SORT_ASC
        ])->all();

        $result = [];
        if ($allHelpdesks) {
            foreach ($allHelpdesks as $helpdesk) {
                array_push($result, $helpdesk->toArray());
            }
        }

        $lastDeskId = HelpDesk::getLastestDesk($clientOpenId, $accountId, $currentHelpdeskId);

        if (!empty($result)) {
            $allOnlineHelpdesks = [];
            foreach ($result as $index => $item) {
                if ($item['isOnline'] && ($item['id'] === $lastDeskId)) {
                    $item['isLastChat'] = true;
                    array_unshift($allOnlineHelpdesks, $item);
                } else if ($item['isOnline']) {
                    array_push($allOnlineHelpdesks, $item);
                }
            }
            $result = $allOnlineHelpdesks;
        }

        return $result;
    }

    /**
     * Update help desk password
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/chat/help-desk<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to update password.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     id: int, the user id, required<br/>
     *     currentPwd: string, the user currentPwd, required<br/>
     *     newPwd: string, the user newPwd, required<br/>
     *     newPwdC: string, the user newPwdC, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the update result, 0 means update successfully, 1 means update fail<br/>
     *     data: array, json array to describe the user updated<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "id" : "547eaf82e9c2fb52478b4567,
     *     "currentPwd" : "6c302344ab2117ee4ce52b7d8952c689",
     *     "newPwd" : "6c302344ab2117ee4ce52b7d8952c689",
     *     "newPwdC" : "6c302344ab2117ee4ce52b7d8952c689"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "result" : "success"
     * }
     * </pre>
     */
    public function actionUpdatepassword()
    {
        $params = $this->getParams();

        if(empty($params['id']) || empty($params['currentPwd']) || empty($params['newPwd']) || empty($params['newPwdC'])) {
            throw new BadRequestHttpException("Parameters missing");
        }

        // validate if the userid is correct
        $user = HelpDesk::findOne(['_id' => new \MongoId($params['id'])]);
        if (empty($user)) {
            throw new BadRequestHttpException("Incorrect userid");
        }

        // validate if the current password is correct
        if (!$user->validatePassword($params['currentPwd'])) {
            throw new InvalidParameterException(['old-password'=>Yii::t('common', 'common_user_currentpwd_error')]);
        }

        // check if the two passwords match
        if ($params['newPwd'] !== $params['newPwdC']) {
            throw new InvalidParameterException(['new-password'=>Yii::t('common','common_user_currentpwd_error')]);
        }

        // check the new password is same as the current password
        if ($params['currentPwd'] == $params['newPwd']) {
            throw new InvalidParameterException(['new-password'=>Yii::t('chat', 'password_error')]);
        }

        // update the user information
        $user->password = HelpDesk::encryptPassword($params['newPwd'], $user->salt);

        if (!$user->save()) {
            throw new ServerErrorHttpException("Save help desk failed!");
        }

        return ['result' => 'success'];
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $helpDesk = HelpDesk::findByPk($id);
        $token = $this->getQuery('accesstoken');

        $helpDesk->load($params, '');

        $lauguage = $helpDesk->language;

        if ($helpDesk->save() && Token::channgeLanguage($token, $lauguage)) {
            $helpDesk->_id .= '';
            return $helpDesk;
        } else {
            throw new ServerErrorHttpException('Fail to update help desk');
        }
    }

    /**
     * Update help desk devices token
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/help-desk/update-devices-token<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to update devices token.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: token,
     *     deskId: string, required<br/>
     *     deviceToken: string, devices token, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "deskId" : "547eaf82e9c2fb52478b4567,
     *     "deviceToken" : "88bf2258a4bae1ec9be98d93e99a3b42b55db03ec3b5f3106415fdba0ea9e0e4"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "result" : "success"
     * }
     * </pre>
     */
    public function actionUpdateDeviceToken()
    {
        $deskId = $this->getParams('deskId');
        $deviceToken = $this->getParams('deviceToken');

        if (empty($deskId)) {
            throw new BadRequestHttpException("Parameters missing");
        }

        $helpDesk = HelpDesk::findByPk(new \MongoId($deskId));
        if (empty($helpDesk)) {
            throw new BadRequestHttpException("Invalid deskId");
        }

        $helpDesk->loginDevice = HelpDesk::MOBILEAPP;
        $helpDesk->deviceToken = $deviceToken;

        if ($helpDesk->save(true, ['loginDevice', 'deviceToken'])) {
            return ['result' => 'success'];
        } else {
            throw new ServerErrorHttpException('Fail to update device token');
        }
    }

    public function actionCheckAuth()
    {
        $secret = TUISONGBAO_SECRET;
        $socketId = $this->getParams('socketId');
        $channelName = $this->getParams('channelName');
        $authData = $this->getParams('authData');//helpdesk is saved as authData
        $chatLogin = $this->getParams('chatLogin');
        $parts = explode(':', $authData);
        //If it is the helpdesk
        $clientId = $parts[1];
        $userData = ['userId' => $clientId, 'userInfo' => []];
        if ('h' === $parts[0]) {
            $client = HelpDesk::findByPk(new \MongoId($clientId));
            $userData['userInfo'] = [
                //'name' => $client->name,
                'badge' => $client->badge,
                'email' => $client->email
            ];
        }

        $userDataJsonStr = json_encode($userData);
        if ($chatLogin) {
            $strToSign = $socketId . ':' . $userDataJsonStr;
            LogUtil::info(['strToSign' => $strToSign, 'secret' => $secret], 'signature');
            $signature = hash_hmac('sha256', $strToSign, $secret);
            LogUtil::info(['signature' => $signature, 'userData' => $userDataJsonStr], 'signature');
            $result = ['signature' => $signature, 'userData' => $userDataJsonStr];
        } else {
            $strToSign = $socketId . ':' . $channelName . ':' . $userDataJsonStr;
            LogUtil::info(['strToSign' => $strToSign, 'channelName'=> $channelName, 'secret' => $secret], 'signature');
            $signature = hash_hmac('sha256', $strToSign, $secret);
            LogUtil::info(['signature' => $signature, 'channelData' => $userDataJsonStr], 'signature');
            $result = ['signature' => $signature, 'channelData' => $userDataJsonStr];
        }
        header("Content-Type:application/json");
        echo json_encode($result);
    }

    /**
     * Login from the WeiXin OAuth entry.
     */
    public function actionLogin()
    {
        $step = $this->getQuery('step');

        if ('1' === $step) {
            $corpId = $this->getQuery('corpId');

            if (empty($corpId)) {
                throw new BadRequestHttpException("Parameters missing");
            }

            $redirectUrl = DOMAIN . "api/chat/help-desk/login";
            $redirectUrl = urlencode($redirectUrl);
            $userOAuthUrl = self::OAUTHURL . "?appid=$corpId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_base&state=$corpId#wechat_redirect";
            LogUtil::info(['corpId' => $corpId, 'oAuthUrl' => $userOAuthUrl], 'wechatcp');
            $this->redirect($userOAuthUrl);
        } else {
            $corpId = $this->getQuery('state');
            $code = $this->getQuery('code');

            if (empty($corpId) || empty($code)) {
                throw new BadRequestHttpException("Parameters missing");
            }

            $result = Yii::$app->weConnect->getUserInfoByOAuth($corpId, $code);
            $userId = $result['userId'];

            if (empty($userId)) {
                $forbiddenUrl = DOMAIN . 'chat/forbidden';
                $this->redirect($forbiddenUrl);
            } else {
                $accountId = HelpDeskSetting::getAccountIdByCorpId($corpId);
                $accountId = new \MongoId($accountId);
                $helpdesk = HelpDesk::getByBadge($userId, $accountId);
                LogUtil::info(['corpId' => $corpId, 'userId' => $userId, 'accountId' => $accountId], 'wechatcp');

                if (empty($helpdesk) || !$helpdesk->isActivated || !$helpdesk->isEnabled) {
                    $forbiddenUrl = DOMAIN . 'chat/forbidden';
                    $this->redirect($forbiddenUrl);
                }

                $tokens = Token::getUnexpiredByUserId($helpdesk->_id);

                if (!empty($tokens)) {
                    $data = ['isForcedOffline' => true, 'id' => $helpdesk->_id . ''];
                    $accountId = $tokens[0]->accountId;
                    Yii::$app->tuisongbao->triggerEvent(ChatConversation::EVENT_FORCED_OFFLINE, $data, [ChatConversation::CHANNEL_GLOBAL . $accountId]);

                    Token::updateAll(['$set' =>['expireTime' => new \MongoDate()]],['_id' => ['$in' => Token::getIdList($tokens)]]);
                }

                $accessToken = Token::createByHelpDesk($helpdesk, true);
                $helpdesk->lastLoginAt = new \MongoDate();
                $helpdesk->save(true, ['lastLoginAt']);

                $accountId = (string) $helpdesk->accountId;
                $helpdeskId = (string) $helpdesk->_id;
                //Update cache indicate that the helpdesk is online
                $response = HelpDesk::join($helpdeskId, $accountId);

                $redirectUrl = DOMAIN . 'chat/wechatcp/helpdesk?accessToken=' . $accessToken['accessToken'];
                $this->redirect($redirectUrl);
            }
        }
    }

    /**
     * Get login user info by accessToken
     */
    public function actionGetUserInfo()
    {
        $accessToken = $this->getParams('accessToken');

        if (empty($accessToken)) {
            throw new BadRequestHttpException("Parameters missing");
        } else {
            $token = Token::getByAccesstoken($accessToken);
            if (!empty($token)) {
                $helpdesk = HelpDesk::getById($token['userId']);

                $accountId = (string) $helpdesk->accountId;
                $helpdeskId = (string) $helpdesk->_id;
                $isFirstLogin = empty($helpdesk->lastLoginAt);

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

                return ['accessToken' => $accessToken, 'userInfo' => $userInfo];
            }
        }
    }
}
