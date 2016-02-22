<?php
namespace backend\modules\uhkklp\models;

use backend\components\PlainModel;

class PushLog extends PlainModel
{
    public static function collectionName()
    {
        return 'uhkklpPushLog';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['request', 'response', 'deviceType']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['request', 'response', 'deviceType']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['request', 'response', 'deviceType'], 'required'],
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['request', 'response', 'deviceType']
        );
    }
}
