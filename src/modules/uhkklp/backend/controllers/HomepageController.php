<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\web\Controller;
use backend\models\Token;
use backend\modules\uhkklp\models\HomeInfo;
use backend\utils\LogUtil;

class HomepageController extends BaseController
{
    public $enableCsrfValidation = false;
    public $defaultAction = 'info';

    private function _setJSONFormat($app) {
        $app->request->parsers = [
            'application/json' => 'yii\web\JsonParser',
            'text/json' => 'yii\web\JsonParser',
        ];
        $app->response->format = 'json';
    }

    public function actionSave()
    {
        $this->_setJSONFormat(Yii::$app);
        $request = Yii::$app->request;
        $data = $request->post();
        if (!empty($data['_id'])) {
            $homeInfo = HomeInfo::findOne([$data['_id']['$id']]);
        }

        if (empty($homeInfo)) {
            $homeInfo = new HomeInfo();
            if (!empty($this->getAccountId())) {
                $data['accountId'] = $this->getAccountId();
            }
        }

        if (!empty($homeInfo->accountId)) {
            unset($data['accountId']);
        }

        $homeInfo->attributes = $data;
        $homeInfo->version = time();

        $homeInfo->save();

        return $homeInfo;
    }

    public function actionGet()
    {
        $this->_setJSONFormat(Yii::$app);
        $data = HomeInfo::findOne(['accountId' => $this->getAccountId()]);
        return empty($data) ? null : $data;
    }

    //api
    public function actionInfo()
    {
        $this->_setJSONFormat(Yii::$app);
        $info = HomeInfo::findOne(['accountId' => $this->getAccountId()]);
        if (empty($info) || $info->type == 'none') {
            $data['code'] = 1101;
            $data['msg'] = '首页信息尚未设置';
            return $data;
        }

        unset($info->_id);
        unset($info->accountId);

        $data['code'] = 200;
        $data['msg'] = 'OK';
        $data['result'] = $info;
        return $data;
    }
}
