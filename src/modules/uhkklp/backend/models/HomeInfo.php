<?php

namespace backend\modules\uhkklp\models;

use yii\mongodb\ActiveRecord;
use yii\mongodb\Query;

class HomeInfo extends ActiveRecord
{
    public static function collectionName()
    {
        return 'uhkklpHomeInfo';
    }

    public function attributes()
    {
        return [
            '_id',
            'type',
            'imgContent',
            'videoContent',
            'accountId',
            'version'
        ];
    }

    public function rules()
    {
        return [
            [['type', 'imgContent', 'videoContent', 'accountId', 'version'], 'safe'],
        ];
    }

    // public function ckVideoUrl()
    // {
    //     if ($this->attributes['type'] == '影音' && empty($this->attributes['videoUrl'])) {
    //         $this->addError('videoUrl', 'required');
    //     }
    // }
}
