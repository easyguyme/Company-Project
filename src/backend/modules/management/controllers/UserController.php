<?php
namespace backend\modules\management\controllers;

use yii\mongodb\Query;
use backend\components\rest\RestController;
use backend\models\User;
use backend\models\Token;
use backend\models\Validation;
use backend\utils\StringUtil;
use backend\utils\MongodbUtil;
use backend\utils\EmailUtil;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use Yii;
use backend\utils\UrlUtil;

class UserController extends RestController
{
    const SUBJECT = '群脉邀请邮件';
    public $modelClass = 'backend\models\User';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    /**
     * Create a new user
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/user<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to create a new user
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     email: string, the user email, required<br/>
     *     role: string, the user role, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to describe the user created<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "email" : "sarazhang@augmentum.com.cn",
     *     "role" : "admin"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"msg": "您当前已成功发送验证邮件到sarazhang@augmentum.com.cn邮箱中", "user": {name:"Devin Jin", avatar:"path/to/avatar", email:"sarazhang@augmentum.com.cn", isActivated:false}}
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $params = $this->getParams();
        if (empty($params['email'])) {
            throw new InvalidParameterException(['email' => Yii::t('common', 'email_is_required')]);
        }
        $params['email'] = mb_strtolower($params['email']);
        if (!StringUtil::isEmail($params['email'])) {
            throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_format_wrong')]);
        }

        $user = User::getByEmail($params['email']);

        if (!empty($user)) {
            throw new InvalidParameterException(['email'=>Yii::t('helpDesk', 'email_has_used')]);
        }

        $user = new User;
        $user->email = $params['email'];
        $user->role = $params['role'];
        $user->avatar = Yii::$app->params['defaultAvatar'];
        $user->isActivated = User:: NOT_ACTIVATED;
        $user->accountId = $this->getAccountId();

        if ($user->save()) {
            $currentUser = $this->getUser();

            $link = UrlUtil::getDomain() . '/site/invite/code?type=2'; //type=2 means invite user account
            $result = EmailUtil::sendInviteEmail($user, $currentUser->name, $link, self::SUBJECT);
            if ($result) {
                return ['user' => $user];
            } else {
                throw new ServerErrorHttpException("validation save fail");
            }
        }

        throw new ServerErrorHttpException("create user fail");
    }

    /**
     * Delete a user
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/user<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to delete a user
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     id: string, the user id, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to return true<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "id" : "547f01dd2c5711421c8b457c",
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"id": "547f01dd2c5711421c8b457c"}
     * }
     * </pre>
     */
    public function actionDelete()
    {
        $params = $this->getParams();

        if (User::deleteAll(['_id' => $params['id']])) {
            return $params['id'];
        }
        throw new ServerErrorHttpException("delete user fail");
    }

    /**
     * View user list
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/user<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to view user list
     * <br/><br/>
     *
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to return true<br/>
     *     <br/><br/>
     *
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': [
     *        {
     *            'name': 'Sara Zhang',
     *            'email': 'sarazhang@augmentum.com.cn,
     *            'id': gjdfjg465464567j43,
     *            'role': 'admin',
     *            'isActivated': true,
     *            'avatar': 'http://www.hello.jpg'
     *        }
     *    ]
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        $userList = User::getByAccount($accountId);

        $tmpList = [];
        foreach ($userList as $user) {
            $name = empty($user['name']) ? '' : $user['name'];
            if (empty($user['consultManager'])) {
                $u = [
                    'name' => $name,
                    'email' => $user['email'],
                    'id' => $user['_id'] . '',
                    'role' => $user['role'],
                    'isActivated' => $user['isActivated'],
                    'avatar' => $user['avatar']
                ];
                $tmpList[] = $u;
            }

        };
        return $tmpList;
    }

    /**
     * Send account invitation email
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/user<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to send account invitation email
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     id: string, the user id, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the create result, 0 means create successfully, 1 means create fail<br/>
     *     data: array, json array to get send email successfully message<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "id" : "547f01dd2c5711421c8b457c",
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'ack' : 1,
     *    'data': {"msg": "您当前已成功发送验证邮件到sarazhang@augmentum.com.cn 邮箱中"}
     * }
     * </pre>
     */
    public function actionSendEmail()
    {
        $id = $this->getParams('id');
        $user = User::findOne(['_id' => $id]);
        $currentUser = $this->getUser();

        $link = UrlUtil::getDomain() . '/site/invite/code?type=2';
        $result = EmailUtil::sendInviteEmail($user, $currentUser->name, $link, self::SUBJECT);
        if ($result) {
            return ['email' => $user->email];
        } else {
            throw new ServerErrorHttpException("validation save fail");
        }
    }

    /**
     * Create a new user
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/user<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to update password.
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
     *    'ack' : 1,
     *    'data': {"msg": "success", "user": {password:"6c302344ab2117ee4ce52b7d8952c689"}}
     * }
     * </pre>
     */
    public function actionUpdatepassword()
    {
        $params = $this->getParams();

        if (empty($params['id']) || empty($params['currentPwd']) || empty($params['newPwd']) || empty($params['newPwdC'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        // validate if the userid is correct
        $user = User::findOne(['_id' => new \MongoId($params['id'])]);
        if (empty($user)) {
            throw new BadRequestHttpException(Yii::t('common', 'incorrect_userid'));
        }

        // validate if the current password is correct
        if (!$user->validatePassword($params['currentPwd'])) {
            throw new InvalidParameterException(['formTip_currentPwd'=>Yii::t('management', 'management_user_currentpwd_error')]);
        } else {
            if ($params['currentPwd'] === $params['newPwd']) {
                throw new InvalidParameterException(['formTip_newPwd'=>Yii::t('management', 'mamagement_user_newpwd_error')]);
            }
        }

        // check if the two passwords match
        if ($params['newPwd'] !== $params['newPwdC']) {
            throw new BadRequestHttpException(Yii::t('management', 'management_user_twopwd_error'));
        }

        // update the user information
        $user->password = User::encryptPassword($params['newPwd'], $user->salt);

        if (!$user->save()) {
            throw new ServerErrorHttpException(Yii::t('management', 'management_user_updatepwd_fail'));
        }

        return ['result' => 'success'];
    }

    public function actionUpdate($id)
    {
        $id = new \MongoId($id);
        $user = User::findOne(['_id' => $id]);
        $user->load($this->getParams(), '');

        if ($user->save() === false && !$user->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        //update the language information in token
        Token::updateAll(['language' => $user->language]);

        return $user;
    }
}
