<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class ActivitySetting extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpActivitySetting';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'image', 'orderImage', 'registrationStartDate', 'registrationEndDate', 'registrationTags', 'registrationDescription',
            'registrationRule', 'registrationNumber', 'orderStartDate', 'orderEndDate', 'orderTags', 'orderDescription', 'orderRule',
            'promotionProducts', 'IsActive', 'activityColor', 'registrationTagString', 'orderTagString']
        );
    }
}
