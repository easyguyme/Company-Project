<?php

namespace backend\modules\uhkklp\models;

use backend\components\PlainModel;
use backend\models\Token;

class KlpAccountSetting extends PlainModel
{
    public static function collectionName()
    {
        return 'uhkklpAccountSetting';
    }

    //site: 'TW' / 'HK'
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['gcmKey', 'site']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['gcmKey', 'site']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            []
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['gcmKey', 'site']
        );
    }

    public static function getAccountSite($accountId)
    {
        $setting = KlpAccountSetting::findOne(['accountId'=>$accountId]);
        if ($setting == null) {
            return "";
        } else {
            return $setting->site;
        }
    }
}
