<?php
namespace backend\controllers;

use backend\components\Controller;
use yii\web\HttpException;
use yii\web\BadRequestHttpException;
use Yii;

class QiniuTokenController extends Controller
{

    public function actions()
    {
        $actions = parent::actions();
        return $actions;
    }

    /**
     * Get qiniu token and bucket
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/qiniu-token/generate<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     token: string, qiniu upload file token<br/>
     *     bucket: string, qiniu bucket<br/>
     *     domain: string, qiniu domain<br/>
     *     uploadDomain: string, qiniu upload domain<br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    token: "QK5YJSJHDKQmlXQq5W4RQqNDTOr5RPCPiVbTqoW-:pjO6D0imUQ-B7JOtcg9ldIfE6IY=:eyJzY29wZSI6InZpbmNlbnRob3UiLCJkZWFkbGluZSI6MTQxNzc0OTUyNn0=",
     *    bucket: "vincenthou",
     *    domain: "http://vincenthou.qiniudn.com",
     *    uploadDomain: "http://upload.qiniu.com"
     * }
     * </pre>
     */
    public function actionGenerate()
    {
        return [
            'token'         => Yii::$app->qiniu->getToken(),
            'bucket'        => Yii::$app->qiniu->bucket,
            'domain'        => Yii::$app->qiniu->domain,
            'uploadDomain'  => Yii::$app->qiniu->uploadDomain
        ];
    }
}
