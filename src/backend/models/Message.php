<?php
namespace backend\models;

use Yii;
use yii\base\Event;
use backend\components\PlainModel;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Model class for Message
 *
 * @property MongoId   $_id
 * @property MongoId   $accountId
 * @property Array     $to {id, target: account or user}
 * @property Array     $sender {id, from: system or user}
 * @property string    $title
 * @property string    $content
 * @property string    $status
 * @property boolean   $isRead
 * @property MongoDate $readAt
 * @property MongoDate $createdAt
 * @author Tony.Zheng
 */
class Message extends PlainModel
{
    //constants for channels
    const CHANNEL_GLOBAL = "presence-message-wm-global";

    //constants of events
    const EVENT_NEW_MESSAGE = "new_message";
    const EVENT_EXPORT_FINISH = "export_finish";

    //constants of status
    const STATUS_WARNING = "warning";
    const STATUS_ERROR = "error";
    const STATUS_SUCCESS = "success";

    //constants of to target
    const TO_TARGET_ACCOUNT = "account";
    const TO_TARGET_USER = "user";

    //constants of sender from
    const SENDER_FROM_SYSTEM = "system";
    const SENDER_FROM_USER = "user";

    //constants of account and system id
    const ID_ACCOUNT = 'account';
    const ID_SYSTEM = 'system';

    /**
     * Declares the name of the Mongo collection associated with message.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'message';
    }

    public function init()
    {
        parent::init();

        Event::on(self::className(), self::EVENT_AFTER_INSERT, function ($event) {
            $message = $event->sender;
            Yii::$app->tuisongbao->triggerEvent(self::EVENT_NEW_MESSAGE, $message->toArray(), [self::CHANNEL_GLOBAL . $message->accountId]);
        });
    }

    public function attributes()
    {
        return ['_id', 'accountId', 'to', 'sender', 'title', 'content', 'status', 'isRead', 'readAt', 'createdAt'];
    }

    public function safeAttributes()
    {
        return ['accountId', 'to', 'sender', 'title', 'content', 'status', 'isRead', 'readAt'];
    }

    /**
    * Returns the list of all rules of Notification.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            [
                [['to', 'sender', 'title', 'content', 'accountId', 'status'], 'required'],
                ['status', 'in', 'range' => [self::STATUS_WARNING, self::STATUS_ERROR, self::STATUS_SUCCESS]],
                ['isRead', 'default', 'value' => false],
            ]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into Notification.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['title', 'content', 'isRead', 'status',
                'sender' => function ($model) {
                    return [
                        'id' => empty($model->sender['id']) ? '' : (string) $model->sender['id'],
                        'from' => empty($model->sender['from']) ? '' : $model->sender['from']
                    ];
                },
                'to' => function ($model) {
                    return [
                        'id' => empty($model->to['id']) ? '' : (string) $model->to['id'],
                        'target' => empty($model->to['target']) ? '' : $model->to['target']
                    ];
                },
                'accountId' => function ($model) {
                    return (string) $model->accountId;
                },
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt, 'Y-m-d H:i:s');
                },
                'readAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->readAt, 'Y-m-d H:i:s');
                }
            ]
        );
    }
}
