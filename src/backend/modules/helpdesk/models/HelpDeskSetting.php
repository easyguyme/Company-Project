<?php
namespace backend\modules\helpdesk\models;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\components\BaseModel;
use backend\behaviors\ChannelBehavior;
use backend\utils\TimeUtil;
use backend\utils\UrlUtil;

/**
 * Model class for helpDeskSetting.
 *
 * The followings are the available columns in collection 'helpDeskSetting':
 * @property MongoId    $_id
 * @property int        $maxWaitTime
 * @property int        $maxClient
 * @property string     $ondutyTime
 * @property string     $offdutyTime
 * @property string     $wechatcp
 * @property array      $systemReplies
 * @property array      $channels
 * @property array      $websites
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 **/
class HelpDeskSetting extends BaseModel
{
    //instance for HelpDeskSetting
    private static $_instance;

    //the helpdesk script path in frontend
    const SCRIPT_PATH = '/build/chat/helpdesk.js';

    //reply type constants
    const REPLY_WAITTING = 'waitting';
    const REPLY_CLOSE = 'close';
    const REPLY_NONWORKING = 'nonworking';
    const REPLY_BRAKE = 'brake';
    const REPLY_SUCCESS = 'success';
    const REPLY_DROPING = 'droping';
    const REPLY_ERROR = 'error';
    const REPLY_CUSTOM = 'custom';

    /**
     * Declares the name of the Mongo collection associated with helpDeskSetting.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'helpDeskSetting';
    }

    /**
     * Returns the list of all attribute names of helpDeskSetting.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['maxWaitTime', 'maxClient', 'ondutyTime', 'offdutyTime', 'systemReplies', 'channels', 'websites', 'wechatcp']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['maxWaitTime', 'maxClient', 'ondutyTime', 'offdutyTime', 'systemReplies']
        );
    }

    /**
     * Returns the list of all rules of user.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['maxWaitTime', 'default', 'value' => Yii::$app->params['helpdesk_max_wait_time']],
                ['maxClient', 'default', 'value' => Yii::$app->params['helpdesk_max_clients']],
                ['ondutyTime', 'default', 'value' => Yii::$app->params['helpdesk_onduty_time']],
                ['offdutyTime', 'default', 'value' => Yii::$app->params['helpdesk_offduty_time']],
                ['systemReplies', 'default', 'value' => Yii::$app->params['helpdesk_system_replies']]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into helpDeskSetting.
     */
    public function fields()
    {
        $fields = array_merge(
            parent::fields(),
            ['maxWaitTime', 'maxClient', 'ondutyTime', 'offdutyTime', 'systemReplies', 'channels', 'websites', 'wechatcp']
        );

        return $fields;
    }

    /**
     * validator for field 'systemReplies'
     * @author Devin Jin
     **/
    public function validateSystemReplies($attribute)
    {
        //for field systemReplies only
        if ($attribute != 'systemReplies') {
            return true;
        }

        $systemReplies = $this->$attribute;

        if (!is_array($systemReplies)) {
            $this->addError('systemReplies', 'systemReplies should be an array');
        }

        $requiredFields = ['name', 'type', 'replyText', 'isEnabled'];

        //validate each field in systenReplies
        foreach ($systemReplies as $systemReply) {
            //validate the required fields in systemReplies
            foreach ($requiredFields as $field) {
                if (empty($systemReply[$field])) {
                    $this->addError('systemReplies', 'systemReplies.' . $field . 'is required');
                }
            }
        }
    }

    /**
     * Get the instance of the HelpDeskSetting according to the accountId
     * @param $accountId MongoId
     * @return Object<HelpDeskSetting>
     * @author Devin Jin
     **/
    public static function getInstance($accountId)
    {
        if (!self::$_instance instanceof self) {
            //In case that the account ID is string type, new helpdesk setting will be created (Bug #361)
            if ('string' === gettype($accountId)) {
                $accountId = new \MongoId($accountId);
            }
            $setting = self::findOne(['accountId' => $accountId]);

            if (empty($setting)) {
                $setting = new self;
                $setting->accountId = $accountId;

                if (!$setting->save()) {
                    throw new ServerErrorHttpException('Failed to create the object<HelpDeskSetting> for unknown reason.');
                }
            }

            $setting->attachBehavior('ChannelBehavior', new ChannelBehavior());
            $setting->syncHelpdeskChannels();

            self::$_instance = $setting;
        }

        return self::$_instance;
    }

    /**
     * Get the code
     * The code is used to add the help desk into website
     * @param  string $url the website url
     * @param  string $accountId
     * @return string
     */
    public static function getCode($url, $accountId)
    {
        // Just mock
        $hostInfo = UrlUtil::getDomain();
        $scriptPath = self::SCRIPT_PATH;
        return "<script id='wm-chat-script' account='$accountId' host='$hostInfo' src='$hostInfo$scriptPath'></script>";
    }

    /**
     * Check the time whether in the working hours of the account when client connect helpdesk system
     * @param string $accountId
     * @return boolean
     */
    public static function isInWorkingHours($accountId)
    {
        $helpDeskSetting = self::getInstance($accountId);
        return TimeUtil::checkHourInterval($helpDeskSetting->ondutyTime, $helpDeskSetting->offdutyTime, 0);
    }

    /**
     * Get the max connected client count of helpdesk
     * @param string $accountId
     * @return number
     */
    public static function getMaxClientCount($accountId)
    {
        $helpDeskSetting = self::getInstance($accountId);
        return $helpDeskSetting->maxClient;
    }

    /**
     * Get the account id by using the corpId of the wechatcp field.
     * @param string $corpId
     * @return string accountId
     */
    public static function getAccountIdByCorpId($corpId)
    {
        $helpDeskSetting = self::findOne(['wechatcp.corpId' => $corpId, 'isDeleted' => false]);
        if (empty($helpDeskSetting)) {
            throw new ServerErrorHttpException('Failed to get the object<HelpDeskSetting> by corpId.');
        } else {
            return $helpDeskSetting->accountId;
        }
    }
}
