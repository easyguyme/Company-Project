<?php
namespace backend\modules\channel\controllers;

use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\GoneHttpException;
use backend\models\Token;
use backend\models\User;
use backend\models\Account;

class TagController extends BaseController
{

    /**
     * Query tags list
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying tags.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried tags detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "items": [
     *      {
     *          "name": "高富帅"
     *      },
     *      {
     *          "name": "白富美"
     *      }
     *  ]
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $result = ['items' => []];
        $accountId = $this->getAccountId();
        $account = Account::findOne(['_id' => new \MongoId($accountId)]);

        if (empty($account)) {
            throw new GoneHttpException("no such account");
        }

        if (!empty($account) && !empty($account['tags'])) {
            $result["items"] = $account['tags'];
        }

        return $result;
    }

    /**
     * Create tag
     *
     * <b>Request Type: </b> POST<br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary </b>: This api is used for create tag.
     *
     * <b>Request Parameters: </b><br/>
     *      name: the name of the tag
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if create fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "msg": "OK"
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $result = ['msg' => 'OK'];
        $tags = $this->getParams('tags');
        $isAutoScanFollower = $this->getParams('isAutoScanFollower');
        $accountId = $this->getAccountId();
        $account = Account::findOne(['_id' => new \MongoId($accountId)]);

        if (empty($account)) {
            throw new GoneHttpException("no such account");
        }

        if (!empty($isAutoScanFollower) && $isAutoScanFollower == true) {
            if (empty($tags) || !is_array($tags)) {
                throw new BadRequestHttpException("Error Processing Request", 1);
            }
        }

        $tag = [];

        foreach ($tags as $item) {
            $tag[] = ['name' => $item];
        }

        if (!Account::updateAll(['$addToSet' => ['tags' => ['$each' => $tag]]], ['_id' => $accountId])) {
            throw new ServerErrorHttpException("update tags failed");
        }

        return $result;
    }
}
