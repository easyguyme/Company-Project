<?php
namespace backend\modules\product\controllers;

use Yii;
use MongoId;
use backend\models\Goods;
use backend\modules\product\models\ReceiveAddress;
use yii\web\ServerErrorHttpException;

class ReceiveAddressController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\ReceiveAddress';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete']);
        return $actions;
    }

    public function actionDelete($id)
    {
        $idList = explode(',', $id);
        foreach ($idList as $key => $perId) {
            $idList[$key] = new MongoId($perId);
        }

        if (ReceiveAddress::deleteAll(['_id' => ['$in' => $idList]]) == false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        //update goods receive address
        foreach ($idList as $id) {
            Goods::updateAll(['$pull' => ['addresses' => $id]], ['addresses' => ['$all' => [$id]], 'receiveModes' => ['$all' => [Goods::RECEIVE_MODE_SELF]]]);
        }
        Yii::$app->getResponse()->setStatusCode(204);
    }
}
