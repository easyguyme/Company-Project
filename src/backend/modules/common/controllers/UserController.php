<?php
namespace backend\modules\common\controllers;

use backend\components\rest\RestController;
use backend\models\User;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use Yii;
use backend\models\Token;

/**
 * User Controller for common module
 * Update user info and password
 */
class UserController extends RestController
{
    public $modelClass = 'backend\models\User';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    /**
     * Update user password
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/common/user<br/><br/>
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

        if (empty($params['id'])) {
            throw new BadRequestHttpException("Parameters missing");
        }

        // validate if the userid is correct
        $user = User::findOne(['_id' => new \MongoId($params['id'])]);
        if (empty($user)) {
            throw new BadRequestHttpException("Incorrect userid");
        }

        if (empty($params['currentPwd']) || $params['currentPwd'] === md5('')) {
            throw new InvalidParameterException(['old-password'=>Yii::t('common', 'required_filed')]);
        }
        // validate if the current password is correct
        if (!$user->validatePassword($params['currentPwd'])) {
            throw new InvalidParameterException(['old-password'=>Yii::t('common', 'common_user_currentpwd_error')]);
        }

        if (empty($params['newPwd']) || $params['newPwd'] === md5('')) {
            throw new InvalidParameterException(['new-password'=>Yii::t('common', 'required_filed')]);
        }
        if (empty($params['newPwdC']) || $params['newPwdC'] === md5('')) {
            throw new InvalidParameterException(['confirm-password'=>Yii::t('common', 'required_filed')]);
        }
        // check if the two passwords match
        if ($params['newPwd'] !== $params['newPwdC']) {
             throw new InvalidParameterException(['new-password'=>Yii::t('common', 'common_user_currentpwd_error')]);
        }

        // check the new password is same as the current password
        if ($params['currentPwd'] == $params['newPwd']) {
            throw new InvalidParameterException(['new-password'=>Yii::t('common', 'newpwd_equals_old_error')]);
        }

        // update the user information
        $user->password = User::encryptPassword($params['newPwd'], $user->salt);

        if (!$user->save()) {
            throw new ServerErrorHttpException("Save user failed!");
        }

        return ['result' => 'success'];
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();

        $user = User::findByPk($id);
        $token = $this->getAccessToken();
        $name = $this->getParams('name');

        if (empty($name)) {
            throw new InvalidParameterException(['name' => Yii::t('common', 'required_filed')]);
        }

        $user->load($params, '');

        $lauguage = $user->language;

        if ($user->save() && Token::channgeLanguage($token, $lauguage)) {
            $user->_id .= '';
            return $user;
        } else {
            throw new ServerErrorHttpException('Fail to update user');
        }
    }
}
