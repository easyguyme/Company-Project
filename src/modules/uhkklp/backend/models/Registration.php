<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class Registration extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpRegistration';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['userId', 'name', 'activityName', 'mobile', 'restaurantName', 'businessForm', 'perPrice', 'address', 'city', 'perComingDay', 'registrationNumber',
            'lineName', 'registrationTime', 'restaurantId', 'confirmRegistration']
        );
    }
}
