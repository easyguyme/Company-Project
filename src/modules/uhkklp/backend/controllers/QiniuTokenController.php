<?php
namespace backend\modules\uhkklp\controllers;

use yii\web\Controller;
use Yii;

class QiniuTokenController extends Controller
{
    public $enableCsrfValidation = false;

    private function _setJSONFormat($app) {
        $app->request->parsers = [
            'application/json' => 'yii\web\JsonParser',
            'text/json' => 'yii\web\JsonParser',
        ];
        $app->response->format = 'json';
    }

    public function actionGenerate()
    {
        $this->_setJSONFormat(Yii::$app);
        $key = Yii::$app->request->get('key');
        return [
            'name'          => $key,
            'token'         => Yii::$app->qiniu->getToken($key),
            'bucket'        => Yii::$app->qiniu->bucket,
            'domain'        => Yii::$app->qiniu->domain,
            'uploadDomain'  => Yii::$app->qiniu->uploadDomain
        ];
    }
}
