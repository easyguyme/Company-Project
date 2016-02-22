<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class Order extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpOrder';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['userId', 'name', 'activityName', 'mobile', 'restaurantName', 'address', 'city', 'productor', 'product', 'orderTime', 'lineName', 'restaurantId']
        );
    }
}
