<?php
namespace backend\modules\management\controllers;

use backend\components\rest\RestController;
use backend\models\Applications;
use backend\models\Account;
use backend\models\Token;
use Yii;
use yii\web\BadRequestHttpException;

class ApplicationController extends RestController
{
    public $modelClass = 'backend\models\Applications';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        return $actions;
    }

    /**
     * Create a new app key
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/app-keys<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to create a new app key
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     name: string, the app name, required<br/>
     *     content: string, the app description, required<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "name" : "不宅人",
     *     "content" : "不宅人是一款徒步APP"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "id": "5524d4ac2736e7f5048b4567",
     *    "name": "爱不宅",
     *    "content" : "不宅人是一款徒步APP",
     *    "privateKey": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InV3aW1zY3Ryb2UifQ.eyJ1aWQiOiI1NGEwZTBkMTI3MzZlN2QyMDQ4YjQ1NjkiLCJzY29wZXMiOltdLCJhcHAiOiI1NTI0ZDRhYzI3MzZlN2Y1MDQ4YjQ1NjcifQ.BRVjkrm7M5speEDhVpCVsIKkJioD8PVcAhxC-Zm9P5g"
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $name = $this->getParams('name');
        $content = $this->getParams('content', '');
        $icon = $this->getParams('icon', '');
        $app = new Applications();
        $app->_id = new \MongoId();
        $app->name = $name;
        $app->content = $content;
        $app->icon = $icon;
        $token = Token::getToken();
        $app->accountId = $token->accountId;
        $app->generateKey();
        return $app->validateSave();
    }

    /**
     * Refresh app private key
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/app-key/refresh/{appKeyId}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to refresh app private key
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "id": "5524d4ac2736e7f5048b4567",
     *    "name": "爱不宅",
     *    "privateKey": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InV3aW1zY3Ryb2UifQ.eyJ1aWQiOiI1NGEwZTBkMTI3MzZlN2QyMDQ4YjQ1NjkiLCJzY29wZXMiOltdLCJhcHAiOiI1NTI0ZDRhYzI3MzZlN2Y1MDQ4YjQ1NjcifQ.BRVjkrm7M5speEDhVpCVsIKkJioD8PVcAhxC-Zm9P5g"
     * }
     * </pre>
     */
    public function actionRefresh($id)
    {
        $token = Token::getToken();
        $appId = new \MongoId($id);
        $app = Applications::findByPk($appId);
        if (empty($app)) {
            throw new BadRequestHttpException(Yii::t('common', 'incorrect_appkeyid'));
        }
        $app->generateKey();
        return $app->validateSave();
    }

    /**
     * Refresh account app key
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/app-key/refresh-key<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to refresh account app key
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "accessKey": "j57yguwea3",
     *     "secretKey": "hddbb36a5agy1u4vwov1612svlp537c2bcvg8cnc",
     *     "keyCreatedAt": 1428479366
     * }
     * </pre>
     */
    public function actionRefreshKey()
    {
        $token = Token::getToken();
        $account = Account::findByPk($token->accountId);
        $account->generateKey();
        $result = $account->getKey();
        return $account->validateSave($result);
    }

    /**
     * Get account app key
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/app-key/key<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to get account app key
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "accessKey": "j57yguwea3",
     *     "secretKey": "hddbb36a5agy1u4vwov1612svlp537c2bcvg8cnc",
     *     "keyCreatedAt": 1428479366
     * }
     * </pre>
     */
    public function actionKey()
    {
        $token = Token::getToken();
        $account = Account::findByPk($token->accountId);
        return $account->getKey();
    }
}
