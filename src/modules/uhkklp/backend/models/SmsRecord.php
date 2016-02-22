<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class SmsRecord extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpSmsRecord';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['modelContent', 'sendTime', 'importResultList', 'isSend', 'smsBatch', 'token']
        );
    }
}
