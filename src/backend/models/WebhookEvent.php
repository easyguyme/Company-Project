<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * This is the sensitiveOperation model
 *
 * The followings are the available columns in collection 'socialEvent':
 * @property MongoId    $_id
 * @property string     $channel
 * @property string     $type
 * @property string     $module
 * @property MongoDate  $startAt
 * @property MongoDate  $endAt
 * @author Tony Zheng
 **/
class WebhookEvent extends PlainModel
{
    // Event types
    const EVENT_TYPE_MSG = 'msg';
    const EVENT_TYPE_EVENT = 'event';

    // Map names
    const MSG_TYPE_MAP = 'msgTypeMap';
    const EVENT_TYPE_MAP = 'eventTypeMap';

    // Rule status
    const ENABLE_RULE = 'ENABLE';
    const DISENABLE_RULE = 'DISABLE';

    // Data types
    const DATA_TYPE_MSG_TEXT = 'text';
    const DATA_TYPE_MSG_IMAGE = 'image';
    const DATA_TYPE_MSG_VOICE = 'voice';
    const DATA_TYPE_MSG_VIDEO = 'video';
    const DATA_TYPE_MSG_SHORT_VIDEO = 'shortvideo';
    const DATA_TYPE_MSG_LOCATION = 'location';
    const DATA_TYPE_MSG_LINK = 'link';
    const DATA_TYPE_MSG_PAYMENT = 'payment_notification';

    const DATA_TYPE_EVENT_CLICK = 'click';
    const DATA_TYPE_EVENT_VIEW = 'view';
    const DATA_TYPE_EVENT_SCAN = 'scan';
    const DATA_TYPE_EVENT_SUBSCRIBE = 'subscribe';
    const DATA_TYPE_EVENT_UNSUBSCRIBE = 'unsubscribe';
    const DATA_TYPE_EVENT_MENTION = 'mention';
    const DATA_TYPE_EVENT_LOCATION = 'location';
    const DATA_TYPE_EVENT_ENTER = 'enter';

    // Rule names
    const RULE_MSG_TEXT = 'TEXT';
    const RULE_MSG_IMAGE = 'IMAGE';
    const RULE_MSG_VOICE = 'VOICE';
    const RULE_MSG_VIDEO = 'VIDEO';
    const RULE_MSG_SHORT_VIDEO = 'SHORT_VIDEO';
    const RULE_MSG_LOCATION = 'LOCATION';
    const RULE_MSG_LINK = 'LINK';

    const RULE_EVENT_CLICK = 'CLICK';
    const RULE_EVENT_VIEW = 'VIEW';
    const RULE_EVENT_SCAN = 'SCAN';
    const RULE_EVENT_SUBSCRIBE = 'SUBSCRIBE';
    const RULE_EVENT_UNSUBSCRIBE = 'UNSUBSCRIBE';
    const RULE_EVENT_MENTION = 'MENTION';
    const RULE_EVENT_LOCATION = 'LOCATION';
    const RULE_EVENT_ENTER = 'ENTER';

    /**
     * Declares the name of the Mongo collection associated with webhookEvent.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'webhookEvent';
    }

    /**
     * Returns the list of all attribute names of channel.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['channel', 'type', 'module', 'startAt', 'endAt']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['channel', 'type', 'module', 'startAt', 'endAt']
        );
    }

    /**
     * Returns the list of all rules of captcha.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [[['channel', 'type', 'module', 'startAt', 'endAt', ], 'required']]
        );
    }

    /**
     * Get all data types belong to msg type event
     */
    public static function getMsgTypes()
    {
        return [
            self::DATA_TYPE_MSG_TEXT,
            self::DATA_TYPE_MSG_IMAGE,
            self::DATA_TYPE_MSG_VOICE,
            self::DATA_TYPE_MSG_VIDEO,
            self::DATA_TYPE_MSG_SHORT_VIDEO,
            self::DATA_TYPE_MSG_LOCATION,
            self::DATA_TYPE_MSG_LINK,
            self::DATA_TYPE_MSG_PAYMENT
        ];
    }

    /**
     * Get all data types belong to event type event
     */
    public static function getEventTypes()
    {
        return [
            self::DATA_TYPE_EVENT_CLICK,
            self::DATA_TYPE_EVENT_VIEW,
            self::DATA_TYPE_EVENT_SCAN,
            self::DATA_TYPE_EVENT_SUBSCRIBE,
            self::DATA_TYPE_EVENT_UNSUBSCRIBE,
            self::DATA_TYPE_EVENT_MENTION,
            self::DATA_TYPE_EVENT_LOCATION,
            self::DATA_TYPE_EVENT_ENTER
        ];
    }

    /**
     * Get all rule names belong to msg type map
     */
    public static function getMsgRules()
    {
        return [
            self::RULE_MSG_TEXT,
            self::RULE_MSG_IMAGE,
            self::RULE_MSG_VOICE,
            self::RULE_MSG_VIDEO,
            self::RULE_MSG_SHORT_VIDEO,
            self::RULE_MSG_LOCATION,
            self::RULE_MSG_LINK
        ];
    }

    /**
     * Get all rule names belong to event type map
     */
    public static function getEventRules()
    {
        return [
            self::RULE_EVENT_CLICK,
            self::RULE_EVENT_VIEW,
            self::RULE_EVENT_SCAN,
            self::RULE_EVENT_SUBSCRIBE,
            self::RULE_EVENT_UNSUBSCRIBE,
            self::RULE_EVENT_MENTION,
            self::RULE_EVENT_LOCATION,
            self::RULE_EVENT_ENTER
        ];
    }

    /**
     * Get the webhook rule map used for weconnect request
     * @param  string $status only support ENABLE or DISABLE value
     * @return array the webhook rule map
     */
    public static function getWebhookRuleData($status)
    {
        $data = [];
        $rules = self::getMsgRules();
        $data[self::MSG_TYPE_MAP] = [];
        foreach ($rules as $rule) {
            $data[self::MSG_TYPE_MAP][$rule] = $status;
        }
        $rules = self::getEventRules();
        $data[self::EVENT_TYPE_MAP] = [];
        foreach ($rules as $rule) {
            $data[self::EVENT_TYPE_MAP][$rule] = $status;
        }
        return $data;
    }

    public static function findByCondition($condition, $one)
    {
        return parent::findByCondition($condition, $one);
    }
}
