<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;

class VersionInfo extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpVersionInfo';
    }

    public function attributes()
    {
        return [
            '_id',
            'ios',
            'android',
            'accountId'
        ];
    }

    public function rules()
    {
        return [
            [['ios', 'android', 'accountId'], 'safe'],
        ];
    }
}
