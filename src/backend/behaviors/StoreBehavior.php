<?php
namespace backend\behaviors;

use Yii;
use yii\base\Behavior;
use backend\models\Store;
use backend\models\Staff;

class StoreBehavior extends Behavior
{
    /**
     * @param $store, object
     */
    public function deleteInfoByStore($store)
    {
        //delete qrcode
        Store::deleteStoreAllQrcode($store);

        $location = $store->location;
        unset($location['detail']);

        $args = [
            'removeLocation' => $location,
            'storeId' => (string)$store->_id,
            'accountId' => (string)$store->accountId,
            'description' => 'Direct: Delete store location from storeLocation collection'
        ];
        Yii::$app->job->create('backend\modules\store\job\Location', $args);

        Staff::deleteAll(['storeId' => $store->_id]);
    }
}
