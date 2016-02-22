<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class SmsResultModel extends BaseModel {

    public static function collectionName()
    {
        return 'uhkklpSmsResultModel';
    }

    //smsBatch 短信批次 區別相同模板下多次發送
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalRecord', 'successRecord', 'failureRecord', 'modelContent', 'smsBatch']
        );
    }
}
