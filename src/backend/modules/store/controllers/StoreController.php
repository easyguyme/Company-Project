<?php

namespace backend\modules\store\controllers;

use Yii;
use MongoId;
use yii\web\BadRequestHttpException;
use backend\models\Store;
use backend\models\Staff;
use backend\models\StoreGoods;

class StoreController extends BaseController
{
    public function actionView($id)
    {
        $id = new MongoId($id);
        $store = Store::findByPk($id);

        if (empty($store)) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        $location = $store->getStoreLocation();
        $result = $store->toArray();
        //$result['location'] = $location;
        $accountId = $this->getAccountId();
        $result['storeGoods'] = StoreGoods::getTotal($id, $accountId);
        $result['staff'] = Staff::getTotal($id, $accountId);
        return $result;
    }
}
