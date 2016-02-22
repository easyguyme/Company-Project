<?php
namespace backend\utils;

use Yii;

/**
 * This is class file for class StringUtil
 * It contains the common string related functions
 *
 * @author Harry Sun
 **/

class LanguageUtil
{
    const DEFAULT_LANGUAGE = 'zh_cn';
    const LANGUAGE_ZH = 'zh_cn';
    const LANGUAGE_EN = 'en_us';
    const LANGUAGE_ZH_TR = 'zh_tr';

    public static function getBrowserLanguage()
    {
        $languages = Yii::$app->request->getAcceptableLanguages();
        $language = self::DEFAULT_LANGUAGE;
        if (is_array($languages) && count($languages) > 0) {

            switch (strtolower($languages[0])) {
                case 'zh': case 'zh-cn': case 'zh-sg': case 'zh-hans':
                    $language = self::LANGUAGE_ZH;
                    break;
                case 'zh-hk': case 'zh-tw': case 'zh-mo': case 'zh-hant':
                    $language = self::LANGUAGE_ZH_TR;
                    break;
                default:
                    $language = self::LANGUAGE_EN;
                    break;
            }
        }
        return $language;
    }
}
