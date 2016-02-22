<?php
namespace backend\modules\uhkklp\controllers;

use backend\modules\uhkklp\models\VersionInfo;
use backend\models\Token;
use Yii;

class VersionController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionGet()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['code'=>401, 'msg'=>'No info found with this account Id.'];
        }

        $condition = ['accountId'=>$accountId];
        $versionInfo = VersionInfo::find()->where($condition)->one();
        if (empty($versionInfo)) {
            return ['code'=>402, 'msg'=>'No matched version info found.'];
        }

        $result = array('ios'=>$versionInfo->ios, 'android'=>$versionInfo->android);
        return ['code'=>200, 'msg'=>'ok', 'result'=>$result];
    }

    public function actionSet()
    {
        Yii::$app->request->parsers = [
            'application/json' => 'yii\web\JsonParser'
        ];
        Yii::$app->response->format = 'json';
        $request = Yii::$app->request;
        $data = $request->post();

        if (empty($data['ios']) || empty($data['android'])) {
            return ['code'=>402, 'msg'=>'No version info found.'];
        }

        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return ['code'=>401, 'msg'=>'No info found with this account Id.'];
        }

        $condition = ['accountId'=>$accountId];
        $versionInfo = VersionInfo::find()->where($condition)->one();
        if (empty($versionInfo)) {
            $versionInfo = new VersionInfo();
        }
        $versionInfo->ios = $data['ios'];
        $versionInfo->android = $data['android'];
        $versionInfo->accountId = $accountId;
        $versionInfo->save();

        return ['code'=>200, 'msg'=>'ok'];
    }
}