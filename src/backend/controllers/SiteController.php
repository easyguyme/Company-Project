<?php
namespace backend\controllers;

use backend\components\Controller;
use backend\models\User;
use backend\modules\helpdesk\models\HelpDesk;
use backend\models\Token;
use backend\models\Validation;
use backend\models\Account;
use backend\utils\StringUtil;
use backend\utils\MongodbUtil;
use backend\utils\EmailUtil;
use backend\exceptions\InvalidParameterException;
use yii\web\HttpException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\GoneHttpException;
use Yii;
use backend\models\Captcha;
use backend\utils\LanguageUtil;
use backend\behaviors\CaptchaBehavior;
use backend\utils\UrlUtil;

class SiteController extends Controller
{
    const HELPDESK_INVITATION = 1;
    const ACCOUNT_INVITATION = 2;
    const HELPDESK_RESET_PASSWORD = 4;
    const ACCOUNT_RESET_PASSWORD = 3;

    //email for register send to
    const REGIST_EMAIL = 'quncrm@augmentum.com';

    /**
     * Login
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/site/login<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the users to login.
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
     *     "email" : "devinjin@augmentum.com.cn",
     *     "password" : "aaaaaaaaaaaaaaaaaaaaaaaaa"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"userInfo": {name:"Devin Jin", avatar:"path/to/avatar", enabledModules:['a', 'b', 'c']}}
     * }
     * </pre>
     */
    public function actionLogin()
    {
        $params = $this->getParams();

        if (empty($params['email']) || empty($params['password'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $user = User::getByEmail(mb_strtolower($params['email']));

        if (empty($user)) {
            throw new InvalidParameterException(['email'=>Yii::t('common', 'incorrect_userid')]);
        }

        $account = Account::findByPk($user->accountId);

        if (empty($account) || $account->status !== Account::STATUS_ACTIVATED) {
            throw new BadRequestHttpException(Yii::t('common', 'account_is_unactivated'));
        }

        if (!$user->isActivated) {
            throw new InvalidParameterException(['email'=>Yii::t('common', 'user_not_activate')]);
        }

        if ($user->validatePassword($params['password'])) {
            $accessToken = Token::create($user);

            $userInfo = [
                'name' => $user->name,
                'email' => $user->email,
                'language' => $user->language,
                'avatar' => empty($user->avatar) ? '' : $user->avatar,
                'enabledModules' => $accessToken['enabledMods'],
                'role' => $user->role,
                'id' => $user->_id . '',
                'accountId' => (string) $user->accountId,
                'company' => $account->company
            ];
            $this->setAccessToken($accessToken['accessToken']);
            return ['userInfo' => $userInfo];
        } else {
            throw new InvalidParameterException(['password'=>Yii::t('common', 'password_error')]);
        }
    }

    /**
     * Register billing account
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/site/register<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for registering user.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string, the user name<br/>
     *     email: string, the user email<br/>
     *     password: string, the user password<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 1 means create successfully, 0 means create fail<br/>
     *     message: string, if create fail, it contains the error message<br/>
     *     data: array, json array to describe all users detail information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "name" : "harrysun",
     *     "email" : "harrysun1@augmentum.com.cn",
     *     "company" : "Augmentum",
     *     "phone" : "13027785897",
     *     "captcha" : "123456"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'message': 'Register success.'
     * }
     * </pre>
     */
    public function actionRegister()
    {
        //set language zh_cn when sign up
        \Yii::$app->language = LanguageUtil::LANGUAGE_ZH;

        $data = $this->getParams();

        if (empty($data['name']) || empty($data['email']) || empty($data['company']) ||
            empty($data['phone']) || empty($data['captcha']) || empty($data['position'])) {
            throw new BadRequestHttpException('missing param');
        }

        $phone = $data['phone'];
        $this->attachBehavior('CaptchaBehavior', new CaptchaBehavior);
        $this->checkCaptcha($phone, $data['captcha']);

        $email = mb_strtolower($data['email']);
        $user = User::getByEmail($email);
        if (!empty($user)) {
            //check if user active fail, send email again
            if (!$user->isActivated) {
                $validation = Validation::getByUserId($user->_id);
                return $this->_sendRegistEmail($user->_id, $data, (string)$user->accountId, $validation);
            } else {
                throw new InvalidParameterException(['email' => \Yii::t('common', 'unique_feild_email')]);
            }
        }

        $accountId = Account::create($data['company'], $phone, $data['name']);
        $user = new User();
        $user->email = $email;
        $user->position = $data['position'];
        $user->accountId = $accountId;
        $user->role = User::ROLE_ADMIN;
        $user->isActivated = User::NOT_ACTIVATED;
        $user->avatar = Yii::$app->params['defaultAvatar'];
        $user->language = LanguageUtil::DEFAULT_LANGUAGE;

        if ($user->save()) {
            return $this->_sendRegistEmail($user->_id, $data, (string)$accountId);
        } else {
            Account::deleteAll(['_id' => $accountId]);

            if ($user->getErrors('email')) {
                throw  new InvalidParameterException(['email' => \Yii::t('common', 'unique_feild_email')]);
            }

            throw new ServerErrorHttpException('regist fail');
        }
    }

    private function _sendRegistEmail($userId, $userInfo, $accountId, $validation = null)
    {
        //active link availab time
        $linkAvailabDays = \Yii::$app->params['user_active_link_availab_time'];

        $host = UrlUtil::getDomain();
        $mail = Yii::$app->mail;

        if (!empty($validation)) {
            $validation->expire = new \MongoDate(strtotime("+$linkAvailabDays day"));
        } else {
            $validation = new Validation();
            $validation->userId = $userId;
            $validation->code = StringUtil::uuid();
            $validation->expire = new \MongoDate(strtotime("+$linkAvailabDays day"));
        }
        $validation->toValidateAccount = true;

        if ($validation->save()) {
            $vars = [
                'name' => $userInfo['name'],
                'link' => $host . '/site/invite/' . $validation->code . '?type=2',
                'email' => $userInfo['email'],
                'phone' => $userInfo['phone'],
                'company' => $userInfo['company'],
                'position' => $userInfo['position']
            ];
            $mail->setView('//mail/signup', $vars, '//layouts/email');
            $result = $mail->sendMail(self::REGIST_EMAIL, '申请群脉账号', $accountId);
            return ["message" => 'Register success.'];
        } else {
            throw new ServerErrorHttpException('regist fail');
        }
    }

    /**
     * Logout
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/site/logout
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for the user to logout.
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/site/logout
     * </pre>
     *
     **/
    public function actionLogout()
    {
        $accessToken = $this->getAccessToken();

        if (empty($accessToken)) {
            return ['token' => $accessToken];
        }

        Token::deleteAll(['accessToken' => $accessToken]);
        $cookies = Yii::$app->response->cookies;
        $cookies->remove('accesstoken');
        return ['message' => 'OK', 'data' => ''];
    }

    /**
     * Activate
     *
     * <b>Request Type </b>: GET<br/>
     * <b>Request Endpoint </b>: http://{server-domain}/api/site/activate?code=abcd1234abcd1234<br/>
     *
     **/
    public function actionActivate()
    {
        $code = $this->getQuery('code');

        if (empty($code)) {
            $this->_activateFail(0);//此链接无效，请联系管理员
        }

        $validation = Validation::findOne(['code' => $code]);

        if (empty($validation)) {
            $this->_activateFail(0);//此链接无效，请联系管理员
        }

        if (empty($validation->expire) || MongodbUtil::isExpired($validation->expire)) {
            $this->_activateFail(1);//'此链接已过期，请联系管理员'
        }

        $userId = $validation->userId;

        if (User::updateAll(['isActivated' => User::ACTIVATED], ['_id' => $userId])) {
            $validation->delete();
            $this->redirect('/site/activate?type=0&link=' . urlencode('/site/login'));
            Yii::$app->end();
        }

        $this->_activateFail(1);//'此链接已过期，请联系管理员'
    }

    /**
     * Redirect to the error page for activate
     * @param String, $errorMessage
     * @author Devin.Jin
     **/
    private function _activateFail($error)
    {
        $this->redirect('/site/activate?type=1&error=' . $error);
        Yii::$app->end();
    }

    /**
     * Invite a ne user
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/site/invite/{code}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to invite a new user
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     code: string, the validation code, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to describe whether the code is valid<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "code" : "4543hfdhhjrs",
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"email": "sarazhang@augmentum.com.cn", "id": "45435tgdfhdf234"}
     * }
     * </pre>
     */
    public function actionInvite()
    {
        $code = $this->getQuery('code');
        $type = $this->getQuery('type');
        $result = Validation::validateCode($code, false);
        if ($result == Validation::LINK_INVALID) {
            return ['msg' => Validation::LINK_INVALID]; //此链接无效，请联系管理员
        } else if ($result == Validation::LINK_EXPIRED) {
            return ['msg' => Validation::LINK_EXPIRED]; //此链接已过期，请联系管理员
        } else {
            if (!empty($type)) {
                if (in_array($type, [self::ACCOUNT_INVITATION, self::ACCOUNT_RESET_PASSWORD])) {
                    $user = User::findOne(['_id' => $result]);
                } else if (in_array($type, [self::HELPDESK_INVITATION, self::HELPDESK_RESET_PASSWORD])) {
                    $user = HelpDesk::findOne(['_id' => $result]);
                }
                if (in_array($type, [self::ACCOUNT_INVITATION, self::ACCOUNT_INVITATION]) && !empty($user) && $user->isActivated) {
                    return ['msg' => Validation::USER_ACTIVATED]; //此用户已被激活，请直接登录
                }

                if (empty($user)) {
                    return ['msg' => Validation::USER_DELETED]; //此用户已被删除，请联系管理员
                }

            }
            return ['id' => $result . '', 'email' => $user->email];
        }
    }

    /**
     * Activate a new user
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/site/update-info<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for a user to activate account
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string, the user name, required<br/>
     *     password: string, the user password, required<br/>
     *     id: string, the user id, required<br/>
     *     avatar: string, the user avatar, required<br/>
     *     code: string, the user validation code, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to describe user id<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "name" : "sarazhang",
     *     "password" : "45345345gdfgdf",
     *     "id" : "643hfjht567",
     *     "avatar" : "http://www.baidu.com/1.jpg",
     *     "code" : "543gfdg45745sd",
     *
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"id": "5345gdfg45745"}
     * }
     * </pre>
     */
    public function actionUpdateInfo()
    {
        $data = $this->getParams();
        if (empty($data['password']) || empty($data['name']) || empty($data['id']) || $data['password'] === md5('')) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $code = empty($data['code']) ? '' : $data['code'];
        $type = empty($data['type']) ? '' : $data['type'];
        $result = Validation::validateCode($code, false);
        if ($result == Validation::LINK_INVALID) {
            throw new GoneHttpException(Yii::t('common', 'link_invalid'));
        } else if ($result == Validation::LINK_EXPIRED) {
            throw new GoneHttpException(Yii::t('common', 'link_invalid'));
        }

        $salt = StringUtil::rndString(6);
        $password = User::encryptPassword($data['password'], $salt);
        $name = $data['name'];
        $avatar = $data['avatar'];
        $id = $data['id'];
        if (!empty($type) && $type == self::ACCOUNT_INVITATION) {
            $user = User::findOne(['_id' => $id]);
            $accountId = $user->accountId;
            if (empty(User::getByName($accountId, $name))) {
                $user->isActivated = User::ACTIVATED;
                $user->salt = $salt;
                $user->language = Yii::$app->language;
                $user->password = $password;
                $user->name = $name;
                $user->avatar = $avatar;
                $flag = $user->save();
            } else {
                throw new InvalidParameterException(['name'=>Yii::t('common', 'name_exist')]);
            }

        } else if (!empty($type) && $type == self::HELPDESK_INVITATION) {
            $helpDesk = HelpDesk::findOne(['_id' => $id]);
            $accountId = $helpDesk->accountId;
            if (empty(HelpDesk::getByName($accountId, $name))) {
                $helpDesk->isActivated = User::ACTIVATED;
                $helpDesk->language = Yii::$app->language;
                $helpDesk->salt = $salt;
                $helpDesk->password = $password;
                $helpDesk->name = $name;
                $helpDesk->avatar = $avatar;
                $flag = $helpDesk->save();
            } else {
                throw new InvalidParameterException(['name'=>Yii::t('common', 'name_exist')]);
            }
        }

        if ($flag) {
            Validation::deleteAll(['code' => $code]);
            return ['id' => $id, 'type' => $type];
        }
        throw new ServerErrorHttpException('activate fail');
    }

    /**
     * Send Reset password email
     */
    public function actionSendResetPasswordEmail()
    {
        $email = $this->getParams('email');
        $email = mb_strtolower($email);

        if (empty($email)) {
            throw new InvalidParameterException(['reset-email' => Yii::t('common', 'email_is_required')]);
        }
        if (!StringUtil::isEmail($email)) {
            throw new InvalidParameterException(['reset-email' => Yii::t('member', 'email_format_error')]);
        }

        //validate the email
        $user = User::getByEmail($email);

        if (empty($user)) {
            throw new InvalidParameterException(['reset-email' => Yii::t('common', 'incorrect_userid')]);
        }

        if (!empty($user) && !$user->isActivated) {
            throw new InvalidParameterException(['reset-email' => Yii::t('common', 'user_not_activate')]);
        }

        //generate the validation
        $validation = new Validation;
        $validation->userId = $user->_id;
        $validation->expire = new \MongoDate(strtotime('+7 day'));

        if (!$validation->save()) {
            throw new ServerErrorHttpException("Failed to save validation");
        }

        $host = UrlUtil::getDomain();
        $link = $host . '/site/resetpassword?code=' . $validation->code;
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

    public function actionResetpassword()
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
        $user = User::findByPk($userId);

        if (empty($user)) {
            throw new BadRequestHttpException(Yii::t('commmon', 'incorrect_userid'));
        }

        // update the user password
        $user->password = User::encryptPassword($newPassword, $user->salt);

        if (!$user->save()) {
            throw new ServerErrorHttpException("Save user failed!");
        }

        Validation::deleteAll(['userId' => $userId]);

        return ['status' => 'ok'];
    }

    /**
     * Refresh Token
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/site/refresh-token
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for refresh-token.
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/site/refresh-token
     * </pre>
     *
     **/
    public function actionRefreshToken()
    {
        $accesstoken = $this->getAccessToken();
        if (empty($accesstoken)) {
            $accesstoken = $this->getQuery('accesstoken');
        }
        $token = Token::getByAccesstoken($accesstoken);

        if (!empty($token)) {
            $token->expireTime = new \MongoDate(time() + Token::EXPIRE_TIME);
            if ($token->save(true, ['expireTime'])) {
                return ['accessToken' => $token->accessToken];
            } else {
                throw new ServerErrorHttpException('Fail to refresh token');
            }
        } else {
            throw new BadRequestHttpException('Error accesstoken');
        }
    }

    /**
     * Get tokenInfo from mongo
     *
     * <b>Request Type </b>:GET
     * <b>Request Endpoints </b>: http://{server-domain}/api/site/get-accesstoken
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting tokenInfo from mongo
     *
     * <b>Request Example </b>:
     * <pre>
     *  http://{server-domain}/api/site/get-accesstoken
     * </pre>
     *
     **/
    public function actionGetAccesstoken()
    {
        $token = $this->getAccessToken();
        $tokenInfo = Token::getToken($token);
        if (empty($tokenInfo)) {
            return ['tokenInfo' => null];
        } else {
            return ['tokenInfo' => $tokenInfo];
        }

    }
}
