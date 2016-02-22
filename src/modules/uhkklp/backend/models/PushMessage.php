<?php
namespace backend\modules\uhkklp\models;

use backend\components\PlainModel;

class PushMessage extends PlainModel
{

    public static function collectionName()
    {
        return 'uhkklpPushMessage';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['mobile', 'token', 'deviceType', 'messageId', 'response']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['response']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['token', 'deviceType', 'messageId'], 'required'],
            ]
        );
    }
}
