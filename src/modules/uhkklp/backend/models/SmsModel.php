<?php
namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;

class SmsModel extends ActiveRecord {

    public static function collectionName()
    {
        return 'uhkklpSmsModel';
    }

    //groupId 導入批次
    public function attributes()
    {
        return ['_id', 'groupId', 'mobile', 'content', 'accountId'];
    }
}
