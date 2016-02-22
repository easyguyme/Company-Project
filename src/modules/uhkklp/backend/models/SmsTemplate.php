<?php
namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;

class SmsTemplate extends ActiveRecord {

    public static function collectionName()
    {
        return 'uhkklpSmsTemplate';
    }

    public function attributes()
    {
        return ['_id', 'accountId', 'modelContent'];
    }
}
