<?php
/**
 * This is class file for validate the form data for microsite component configuration.
 *
 * @author Vincent Hou
 *
 */

namespace backend\components;

use Yii;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;

class CptConfValidator
{
    const MAX_COVER1_SLIDE_NAME = 16;
    const MAX_COVER1_NAV_NAME = 6;
    const MAX_NAV_NAME = 6;
    const MAX_ALBUM_TITLE = 16;
    const MAX_ALBUM_PIC_DESCRPTION = 200;
    const MAX_LINK_NAME = 16;
    const MAX_PIC_NAME = 16;
    const MAX_CONTACT_NAME = 14;
    //regular expressions
    const TELEPHONE_REGX = '/^1[3|4|5|7|8](\d{9})$/';
    const EMAIL_REGX = '/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/';
    const QQ_REGX = '/^\d{5,10}$/';
    const TABLE_REGX = '/^<table(.)*<\/table>$/';

    /**
    * This is the wrapper for all the validators
    *
    * All the other validation method should follow the pattern validate{cptName}
    *
    * @example
    *      Notice: If no validation is needed, no method is needed to add <br>
    *
    * @param $cptName the name of component
    * @param $data the data to be validated
    *
    */
    public static function validate($cptName, $data)
    {
        if (!isset($data) || empty($data))
        {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $methodName = "self::validate" . ucfirst($cptName);
        $ret = is_callable($methodName) ? call_user_func($methodName, $data) : '';
    }

    /**
    * The api is for the user to validate the pic data.
    * @param $data the data to be validated
    */
    public static function validatePic($data)
    {
        self::validateEmpty($data['imageUrl'], 'imageUrl', 'microsite_picture_required');
        self::validateLength($data['name'], 0, self::MAX_PIC_NAME, 'pic', 'microsite_cover1_slide_name_most_error');
    }

    /**
    * The api is for the user to validate the table data.
    * @param $data the data to be validated
    */
    public static function validateTable($data)
    {
        self::validateEmpty($data['content'], 'table');
        if (!preg_match(self::TABLE_REGX, $data['content'])) {
            throw new InvalidParameterException(['table' => Yii::t('content', 'microsite_table_format_error')]);
        }
    }

    /**
    * The api is for the user to validate the sms data.
    * @param $data the data to be validated
    */
    public static function validateSms($data)
    {
        self::validateEmpty($data['tel'], 'tel');
        self::validateTelephoneNumber($data['tel'], 'tel');
        self::validateLength($data['smsText'], 0, 10, 'smsText', 'microsite_sms_title_error');
    }

    /**
    * The api is for the user to validate the html data.
    * @param $data the data to be validated
    */
    public static function validateHtml($data)
    {
        self::validateEmpty($data['content'], 'html');
    }

    /**
    * The api is for the user to validate the title data.
    * @param $data the data to be validated
    */
    public static function validateTitle($data)
    {
        self::validateEmpty($data['name'], 'title');
    }

    /**
    * The api is for the user to validate the title data.
    * @param $data the data to be validated
    */
    public static function validateTab($data)
    {
        $msg = [];
        foreach ($data['tabs'] as $idx => $tab)
        {
            (empty($tab['name']) && $tab['name'] != '0') && ($msg['tab' . $idx . '-name'] = Yii::t('content','field_required'));
        }

        if (count($msg) != 0)
        {
            throw new InvalidParameterException($msg);
        }
    }

    /**
    * The api is for the user to validate the contact data.
    * @param $data the data to be validated
    */
    public static function validateContact($data)
    {
        self::validateEmpty($data['name'], 'contact-name');
        self::validateLength($data['name'], 1, self::MAX_CONTACT_NAME, 'contact-name', 'microsite_contact_name_error');
        self::validateEmpty($data['tel'], 'contact-tel');
        self::validateEmail($data['email'], 'contact-email');
        self::validateQQNumber($data['qq'], 'contact-qq');
    }

    /**
    * The api is for the user to validate the map data.
    * @param $data the data to be validated
    */
    public static function validateMap($data)
    {
        self::validateEmpty($data['name'], 'mapName');
        self::validateEmpty($data['town'], 'mapTown');

        if (!isset($data['url']) || empty($data['url']))
        {
            if ($data['isDisplayMapIcon'] == 'true')
            {
                throw new InvalidParameterException(['relocationAddress'=>Yii::t('content','microsite_relocation_map')]);
            }
        }
    }

    /**
    * The api is for the user to validate the cover1 data.
    * @param $data the data to be validated
    */
    public static function validateCover1($data)
    {
        if(!empty($data['slideInfo']))
        {
            $slideInfo = $data['slideInfo'];
            foreach ($slideInfo as $index=>$slide)
            {
                self::validateEmpty($slide['name'], 'picName' . $index);
                self::validateLength($slide['name'], 1, self::MAX_COVER1_SLIDE_NAME, 'picName' . $index, 'microsite_cover1_slide_name_most_error');
                self::validateEmpty($slide['pic'], 'pic' . $index, 'microsite_picture_required');
            }
        }
        else
        {
            throw new BadRequestHttpException(Yii::t('content', 'common_miss_required'));
        }
        if(!empty($data['navInfo']))
        {
            $navInfo = $data['navInfo'];
            foreach ($navInfo as $index => $nav)
            {
                self::validateEmpty($nav['iconUrl'], 'iconUrl' . $index, 'microsite_cover1_nav_icon_required');
                self::validateEmpty($nav['name'], 'navName'. $index);
                self::validateLength($nav['name'], 1, self::MAX_COVER1_NAV_NAME, 'navName' . $index, 'microsite_cover1_nav_name_most_error');
                self::validateEmpty($nav['linkUrl'], 'nav-linkUrl' . $index);
            }
        }
    }

    /**
    * The api is for the user to validate the cover1 data.
    * @param $data the data to be validated
    */
    public static function validateCover2($data)
    {
        //TODO
    }

    /**
    * The api is for the user to validate the slide data.
    * @param $data the data to be validated
    */
    public static function validateSlide($data)
    {
        if(!empty($data['info']))
        {
            $info = $data['info'];
            if(count($info) < 2)
            {
                throw new BadRequestHttpException(Yii::t('content', 'microsite_slide_count'));
            }
            foreach ($info as $index=>$slide)
            {
                if(empty($slide['pic'])) {
                    throw new InvalidParameterException(['pic' . $index => Yii::t('content', 'microsite_picture_required')]);
                }
                !empty($slide['name']) && self::validateLength($slide['name'], 0, 16, 'slide-picname-' . $index);
            }
        }
    }

    /**
    * The api is for the user to validate the text data.
    * @param $data the data to be validated
    */
    public static function validateText($data)
    {
        self::validateEmpty($data['text'], 'textInfo');
    }

    /**
    * The api is for the user to validate the tel data.
    * @param $data the data to be validated
    */
    public static function validateTel($data)
    {
        self::validateEmpty($data['tel'], 'tel');
        self::validateTelephoneNumber($data['tel'], 'tel');
        if (!empty($data['tag']))
        {
            self::validateLength($data['tag'], 0, 10, 'tag', 'microsite_tel_title_error');
        }
    }

    /**
     * The api is for the user to validate the nav data.
     * @param $data the data to be validated
    */
    public static function validateNav($data)
    {
        foreach ($data['infos'] as $index => $info)
        {
            self::validateEmpty($info['name'], 'name' . $index, 'microsite_navigation_name_required');
            self::validateLength($info['name'], 1, self::MAX_NAV_NAME, 'name' . $index, 'microsite_navigation_name_most_error');
            self::validateEmpty($info['linkUrl'], 'linkUrl' . $index, 'microsite_navigation_link_required');
        }
    }

    /**
     * The api is for the user to validate the atlas data.
     * @param $data the data to be validated
    */
    public static function validateAlbum($data)
    {
        self::validateEmpty($data['title'], 'title');
        self::validateLength($data['title'], 1, self::MAX_ALBUM_TITLE, 'title', 'microsite_album_title_most_error');

        foreach ($data['album'] as $index => $picture)
        {
            self::validateEmpty($picture['url'], 'picture' . $index, 'microsite_picture_required');
            self::validateLength($picture['description'], 0, self::MAX_ALBUM_PIC_DESCRPTION, 'description' . $index, 'microsite_album_description_most_error');
        }
    }

    /**
     * The api is for the user to validate the link data.
     * @param $data the data to be validated
    */
    public static function validateLink($data)
    {
        self::validateEmpty($data['name'], 'name');
        self::validateLength($data['name'], 1, self::MAX_LINK_NAME, 'name', 'microsite_link_name_length_error');
        self::validateEmpty($data['linkUrl'], 'linkUrl');
    }

    /**
     * The api is for the user to validate the articles data.
     * @param $data the data to be validated
    */
    public static function validateArticles($data)
    {
        self::validateEmpty($data['channelId'], 'channel', 'microsite_component_validate_articles_lack_channel');
        if (empty($data['showNum']))
        {
            throw new InvalidParameterException(['showNum' => Yii::t('content', 'microsite_component_validate_articles_lack_shownum')]);
        }
    }

    /**
     * The api is for the user to validate the cover3 data.
     * @param $data the data to be validated
    */
    public static function validateCover3($data)
    {
        if(!empty($data['navs']))
        {
            $navs = $data['navs'];
            if(count($navs) < 1 || count($navs) > 6)
            {
                throw new BadRequestHttpException(Yii::t('content', 'microsite_cover3_nav_count_error'));
            }
            foreach ($navs as $index=>$slide)
            {
                self::validateEmpty($slide['pic'], 'cover3-nav-pic' . $index, 'microsite_picture_required');
                self::validateEmpty($slide['name'], 'cover3-nav-name' . $index);
                self::validateLength($slide['name'], 1, self::MAX_NAV_NAME, 'cover3-nav-name' . $index, 'microsite_navigation_name_most_error');
                self::validateEmpty($slide['linkUrl'], 'cover3-nav-link'. $index);
            }
        }
        else
        {
            throw new BadRequestHttpException(Yii::t('content', 'common_miss_required'));
        }
    }

    /**
     * Ensure that the value is telephone number
     * @param $value the value to be checked
     * @param $key the id of frontend input element
     * @param $msgKey the key for i18n message
     * @throws InvalidParameterException If the value is not telephone number
    */
    private static function validateTelephoneNumber($value, $key, $msgKey = 'microsite_tel_name_format_error')
    {
        $value = trim($value);
        if (!empty($value) && !preg_match(self::TELEPHONE_REGX, $value))
        {
            throw new InvalidParameterException([$key => Yii::t('content', $msgKey)]);
        }
    }

    /**
     * Ensure that the value is email
     * @param $value the value to be checked
     * @param $key the id of frontend input element
     * @param $msgKey the key for i18n message
     * @throws InvalidParameterException If the value is not email
    */
    private static function validateEmail($value, $key, $msgKey = 'microsite_email_format_error')
    {
        $value = trim($value);
        if (!empty($value) && !preg_match(self::EMAIL_REGX, $value))
        {
            throw new InvalidParameterException([$key => Yii::t('content', $msgKey)]);
        }
    }

    /**
     * Ensure that the value is qq number
     * @param $value the value to be checked
     * @param $key the id of frontend input element
     * @param $msgKey the key for i18n message
     * @throws InvalidParameterException If the value is not qq number
    */
    private static function validateQQNumber($value, $key, $msgKey = 'microsite_qq_format_error')
    {
        $value = trim($value);
        if (!empty($value) && !preg_match(self::QQ_REGX, $value))
        {
            throw new InvalidParameterException([$key => Yii::t('content', $msgKey)]);
        }
    }

    /**
     * Ensure that the value is not empty
     * @param $value the value to be checked
     * @param $key the id of frontend input element
     * @param $msgKey the key for i18n message
     * @throws InvalidParameterException If the value is empty
    */
    private static function validateEmpty($value, $key, $msgKey = 'field_required')
    {
        $value = trim($value);
        if (empty($value) && $value != '0')
        {
            throw new InvalidParameterException([$key => Yii::t('content', $msgKey)]);
        }
    }

    /**
     * Ensure that the value is not empty
     * @param $value the value to be checked
     * @param $maxLength the max length for the field
     * @param $key the id of frontend input element
     * @param $msgKey the key for i18n message
     * @throws InvalidParameterException If the value is not with the min and max length limitation
    */
    private static function validateLength($value, $minLength = 0, $maxLength, $key, $exceedMsgKey = 'exceed_length_limit', $lessThanMsgKey = 'less_than_length_limit')
    {
        $length = mb_strlen($value, 'UTF8');
        if(isset($value))
        {
            if ($length < $minLength) {
                throw new InvalidParameterException([$key => Yii::t('content', $lessThanMsgKey)]);
            }

            if ($length > $maxLength) {
                throw new InvalidParameterException([$key => Yii::t('content', $exceedMsgKey)]);
            }
        }
    }
}
