<?php
namespace backend\modules\helpdesk\models;

use Yii;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\components\BaseModel;
use backend\components\ActiveDataProvider;

/**
 * Model class for ChatMessage.
 *
 * The followings are the available columns in collection 'ChatMessage':
 * @property MongoId   $_id
 * @property Array     $content
 * @property MongoDate $sentTime
 * @property boolean   $isReply
 * @property MongoId   $conversationId
 * @property MongoId   $accountId
 * @property string    $date
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 **/
class ChatMessage extends BaseModel
{
    //constants for message type
    const MSG_TYPE_TEXT = "TEXT";
    const MSG_TYPE_NEWS = "NEWS";

    /**
     * Declares the name of the Mongo collection associated with ChatMessage.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'chatMessage';
    }

    /**
     * Returns the list of all attribute names of ChatMessage.
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
            ['content', 'sentTime', 'isReply', 'conversationId', 'accountId', 'date']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['content', 'sentTime', 'isReply', 'conversationId', 'accountId', 'date']
        );
    }

    /**
     * Returns the list of all rules of ChatMessage.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['content', 'required'],
                ['content', 'validateContent'],
                ['date', 'default', 'value' => date('Y-m-d')]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into ChatMessage.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'content', 'isReply', 'sentTime',
                'conversationId' => function () {
                    return $this->conversationId . '';
                }
            ]
        );
    }

    /**
     * Validator for field 'content'
     */
    public function validateContent($attribute)
    {
        //only validate the field "content"
        if ($attribute !== 'content') {
            return true;
        }

        $content = $this->$attribute;
        if (!is_array($content)) {
            $this->addError($attribute, 'content should be an array');
        }

        $msgType = [self::MSG_TYPE_NEWS, self::MSG_TYPE_TEXT];
        if (empty($content['msgType']) || !in_array($content['msgType'], $msgType)) {
            $this->addError($attribute, 'content.msgType must be text or image.');
        }

        if (!array_key_exists('body', $content)) {
            $this->addError($attribute, 'content.body is required.');
        }
    }

    public static function findByConversation($conversationId)
    {
        return self::find()
            ->where(['conversationId' => $conversationId])
            ->orderBy(['createdAt' => SORT_ASC])
            ->all();
    }

    public static function getByConversations($conversationIds, $perPage, $page, $sentTime = null)
    {
        $condition = ['conversationId' => ['$in' => $conversationIds]];
        if (!empty($sentTime)) {
            $condition = array_merge($condition, ['sentTime' => ['$gt' => $sentTime + 0]]);
        }

        return self::find()
            ->where($condition)
            ->limit($perPage)
            ->offset($page)
            ->orderBy(['createdAt' => SORT_DESC])
            ->all();
    }

    public static function countClientMessage($condition)
    {
        $condition['isReply'] = false;
        return self::count($condition);
    }

    public static function getDailyData($condition)
    {
        $condition['isReply'] = false;
        return self::getCollection()->group(
            ['date' => true],
            ['messageCount' => 0],
            'function(doc, aggregator) {
                aggregator.messageCount++;
            }',
            ['condition' => $condition]
        );
    }

    public static function getLastMessage($conversationId, $isReplay = null)
    {
        $condition = ['conversationId' => $conversationId];

        if (!is_null($isReplay)) {
            $condition = array_merge($condition, ['isReply' => $isReplay]);
        }

        return self::find()
            ->where($condition)
            ->orderBy(['createdAt' => SORT_DESC])
            ->one();
    }

    public static function searchByConversation($conversationId)
    {
        $query = self::find();
        $condition = ['conversationId' => $conversationId];
        $query->where($condition);
        $query->orderBy(['createdAt' => SORT_DESC]);
        return new ActiveDataProvider(['query' => $query]);
    }

    public static function saveRecord($content, $sentTime, $isReply, $conversationId, $accountId)
    {
        $chatMessage = new ChatMessage;
        $chatMessage->content = $content;
        $chatMessage->sentTime = $sentTime;
        $chatMessage->isReply = $isReply;
        $chatMessage->conversationId = $conversationId;
        $chatMessage->accountId = $accountId;

        if (!$chatMessage->save()) {
            LogUtil::error(['message' => 'save chatMessage failed', 'error' => $chatMessage->errors], 'helpdesk');
            throw new ServerErrorHttpException('save chatConversation failed');
        }

        //update the lastChatTime for chatConversation
        ChatConversation::setLastChatTime($sentTime, $conversationId);

        return $chatMessage;
    }
}
