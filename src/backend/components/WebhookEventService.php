<?php
namespace backend\components;

use yii\base\Object;
use yii\base\InvalidParamException;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\models\WebhookEvent;
use Yii;

class WebhookEventService extends Object
{
    const MSG_TYPE = 'msg';
    const EVENT_TYPE = 'event';
    const ENABLE_ACTION = 'ENABLE';
    const DISABLE_ACTION = 'DISABLE';
    const INIFITE_DATE_STR = '2115-12-30 23:59:59';

    /**
     * Subscribe the msg type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of msg type from WeConnect
     * @param integer $startAt the start time timestamp
     * @param integer $endAt the end time timestamp
     */
    public function subscribeMsg($module, $channel, $dataType, $startAt, $endAt = 0)
    {
        return $this->subscribe($module, $channel, $dataType, $startAt, $endAt, self::MSG_TYPE);
    }

    /**
     * Subscribe the event type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of event type from WeConnect
     * @param integer $startAt the start time timestamp
     * @param integer $endAt the end time timestamp
     */
    public function subscribeEvent($module, $channel, $dataType, $startAt, $endAt = 0)
    {
        return $this->subscribe($module, $channel, $dataType, $startAt, $endAt, self::EVENT_TYPE);
    }

    /**
     * Unsubscribe the msg type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of msg type from WeConnect
     * @param integer $startAt the start time timestamp
     */
    public function unsubscribeMsg($module, $channel = '', $dataType = '', $startAt = 0)
    {
        return $this->unsubscribe($module, $channel, $dataType, $startAt, self::MSG_TYPE);
    }

    /**
     * Unsubscribe the event type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of event type from WeConnect
     * @param integer $startAt the start time timestamp
     */
    public function unsubscribeEvent($module, $channel = '', $dataType = '', $startAt = 0)
    {
        return $this->unsubscribe($module, $channel, $dataType, $startAt, self::EVENT_TYPE);
    }

    /**
     * Subscribe the event or message type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of event type from WeConnect
     * @param integer $startAt the start time timestamp
     * @param integer $endAt the end time timestamp
     * @param string $type event or message type
     */
    private function subscribe($module, $channel, $dataType, $startAt, $endAt, $type)
    {
        $this->validateParameters($module, $channel, $dataType, $type);

        if (empty($startAt)) {
            throw new InvalidParamException('startAt parameter is required');
        }

        if (empty($endAt)) {
            $endAt = strtotime(self::INIFITE_DATE_STR);
        }

        if ($startAt > $endAt) {
            throw new InvalidParamException('startAt must be earlier than endAt');
        }

        $one = false;
        $condition = [
            'channel'=> $channel,
            'type'=> $type . '-' . $dataType,
            'module'=> $module,
            'startAt'=> new \MongoDate($startAt),
            'endAt'=> new \MongoDate($endAt),
        ];

        $webhookEvent = WebhookEvent::findByCondition($condition, $one);
        if (count($webhookEvent) > 0) {
            throw new InvalidParamException('The event has been subscribed');
        } else {
            $funciton = [Yii::$app->weConnect, 'update' . ucfirst($type) . 'WebhookRule'];
            //transform inconsistent event name for weconnect API
            if (WebhookEvent::DATA_TYPE_MSG_SHORT_VIDEO === $dataType) {
                $dataType = 'SHORT_VIDEO';
            }
            if (WebhookEvent::DATA_TYPE_MSG_PAYMENT !== $dataType) {
                call_user_func($funciton, $channel, strtoupper($dataType), self::ENABLE_ACTION);
            }
            $webhookEvent = new WebhookEvent;
            $webhookEvent->channel = $channel;
            $webhookEvent->type = $type . '-' . $dataType;
            $webhookEvent->module = $module;
            $webhookEvent->startAt = new \MongoDate($startAt);
            $webhookEvent->endAt = new \MongoDate($endAt);

            if ($webhookEvent->save()) {
                return true;
            } else {
                LogUtil::error(['message' => $webhookEvent->getErrors()], 'webhookEvent');
                return false;
            }
        }
    }

    /**
     * Unsubscribe the event or message type
     * @param string $module the module name
     * @param string $channel the wechat account id
     * @param string $dataType the data type of event type from WeConnect
     * @param integer $startAt the start time timestamp
     * @param string $type event or message type
     * @return boolean Whether the event or message is unsubscribed successfully
     */
    private function unsubscribe($module, $channel = '', $dataType = '', $startAt = 0, $type)
    {
        $this->validateParameters($module, $channel, $dataType, $type);

        $condition = [
            'module'=> $module,
            'channel'=> $channel,
        ];

        if (!empty($type) && !empty($dataType)) {
            $condition['type'] = $type . '-' . $dataType;
        }

        if (!empty($startAt)) {
            $condition['startAt'] = new \MongoDate($startAt);
        }

        $funciton = [Yii::$app->weConnect, 'update' . ucfirst($type) . 'WebhookRule'];
        //transform inconsistent event name for weconnect API
        if (WebhookEvent::DATA_TYPE_MSG_SHORT_VIDEO === $dataType) {
            $dataType = 'SHORT_VIDEO';
        }
        call_user_func($funciton, $channel, strtoupper($dataType), self::DISABLE_ACTION);
        $deletedCount = WebhookEvent::deleteAll($condition);
        return ($deletedCount > 0);
    }

    private function validateParameters($module, $channel, $dataType, $type)
    {
        if (empty($module)) {
            throw new InvalidParamException('module parameter is required');
        }

        if (empty($channel)) {
            throw new InvalidParamException('channel parameter is required');
        }

        $types = call_user_func('backend\models\WebhookEvent::get' . ucfirst($type) . 'Types');
        if (!in_array($dataType, $types)) {
            throw new InvalidParamException('Invalid data type');
        }
    }
}
