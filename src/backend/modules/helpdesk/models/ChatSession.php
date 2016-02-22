<?php
namespace backend\modules\helpdesk\models;

use Yii;
use yii\web\BadRequestHttpException;
use backend\components\PlainModel;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use MongoId;
use MongoDate;
use backend\components\ActiveDataProvider;

/**
 * Model class for ChatConversation.
 *
 * The followings are the available columns in collection 'ChatConversation':
 * @property MongoId   $_id
 * @property String    $conversationId
 * @property Array     $desk
 * @property Array     $client
 * @property int       $startMessageId
 * @property int       $endMessageId
 * @property MongoDate $lastChatTime
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class ChatSession extends PlainModel
{

    //constants for types
    const TYPE_WEBSITE = 'website';
    const TYPE_WECHAT  = 'wechat';
    const TYPE_WEIBO   = 'weibo';
    const TYPE_ALIPAY  = 'alipay';

     /**
     * Declares the name of the Mongo collection associated with ChatSession.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'chatSession';
    }

     /**
     * Returns the list of all attribute names of ChatSession.
     * This method must be overridden by child classes to define available attributes.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['conversationId', 'desk', 'client', 'lastChatTime', 'startMessageId', 'endMessageId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['conversationId', 'desk', 'client', 'lastChatTime', 'startMessageId', 'endMessageId']
        );
    }

    /**
     * Returns the list of all rules of ChatSession.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['conversationId', 'required'],
                ['desk', 'validateDesk'],
                ['client', 'validateClient'],
                ['desk', 'required'],
                ['client', 'required'],
                ['lastChatTime', 'default', 'value' => new MongoDate()],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into ChatSession.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'conversationId', 'client', 'startMessageId', 'endMessageId',
                'desk' => function () {
                    $desk = $this->desk;
                    $desk['id'] = (string) $desk['id'];
                    return $desk;
                },
                'lastChatTime' => function () {
                    return MongodbUtil::MongoDate2String($this->lastChatTime);
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                }
            ]
        );
    }

    /**
     * Validator for field 'desk'
     */
    public function validateDesk($attribute)
    {
        //only validate the field "desk"
        if ($attribute !== 'desk') {
            return true;
        }

        $desk = $this->$attribute;
        if (!is_array($desk)) {
            $this->addError($attribute, 'desk should be an array');
        }

        $requiredFields = ['badge', 'id', 'email', 'avatar'];
        foreach ($requiredFields as $field) {
            if (empty($desk[$field])) {
                $this->addError($attribute, 'desk.' . $field . ' is required.');
            }
        }
    }

    /**
     * Validator for field 'client'
     */
    public function validateClient($attribute)
    {
        //only validate the field "client"
        if ($attribute !== 'client') {
            return;
        }

        $client = $this->$attribute;
        if (!is_array($client)) {
            $this->addError($attribute, 'client should be an array');
        }

        $requiredFields = ['nick', 'openId', 'source'];
        foreach ($requiredFields as $field) {
            if (empty($client[$field])) {
                $this->addError($attribute, 'client.' . $field . ' is required.');
            }
        }

        $source = [self::TYPE_WEBSITE, self::TYPE_WECHAT, self::TYPE_WEIBO, self::TYPE_ALIPAY];
        if (!empty($client['source']) && !in_array($client['source'], $source)) {
            $this->addError($attribute, 'client.source must be website, wechat, weibo or alipay.');
        }
    }

    /**
     * Record chatSession when user join a conversation.
     */
    public static function recordChatSession($conversationId, $client, $desk, $accountId, $startMessageId)
    {
        $chatSession = new self;
        $chatSession->conversationId = $conversationId;
        $chatSession->client = $client;
        $chatSession->desk = $desk;
        $chatSession->startMessageId = $startMessageId;
        $chatSession->endMessageId = $startMessageId;
        $chatSession->accountId = is_string($accountId) ? new MongoId($accountId) : $accountId;
        return $chatSession->save();
    }

    public static function updateEndMessageId($accountId, $conversationId, $endMessageId, $lastChatTime)
    {
        $accountId = is_string($accountId) ? new MongoId($accountId) : $accountId;
        $condition = ['accountId' => $accountId, 'conversationId' => $conversationId];
        $chatSession = static::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
        if (!empty($chatSession)) {
            $chatSession->endMessageId = $endMessageId;
            $chatSession->lastChatTime = new MongoDate(strtotime($lastChatTime));
            // Save millisecond
            return $chatSession->save(true, ['endMessageId', 'lastChatTime']);
        }
    }

    public static function search($accountId, $params, $isAll = false)
    {
        $query = static::find();
        $condition = ['accountId' => $accountId];

        if (isset($params['openIds'])) {
            $condition['client.openId'] = ['$in' => $params['openIds']];
        } else if (!empty($params['openId'])) {
            $isAll = true;
            $condition['client.openId'] = $params['openId'];
        }

        if (isset($params['startTime'])) {
            $condition['lastChatTime']['$gte'] = MongodbUtil::msTimetamp2MongoDate($params['startTime']);
        }
        if (isset($params['endTime'])) {
            $condition['lastChatTime']['$lte'] = MongodbUtil::msTimetamp2MongoDate($params['endTime']);
        }
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);

        if ($isAll) {
            return ['items' => $query->all()];
        }
        $searchQuery = ['query' => $query];
        return new ActiveDataProvider($searchQuery);
    }

    public static function getMessageId($accountId, $conversationId)
    {
        $accountId = is_string($accountId) ? new MongoId($accountId) : $accountId;
        $condition = ['accountId' => $accountId, 'conversationId' => $conversationId];
        $chatSession = static::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
        if (empty($chatSession)) {
            throw new BadRequestHttpException("conversation not exist");
        }
        return [$chatSession->endMessageId, $chatSession->startMessageId];
    }

    /**
     * Get last by openIds
     * @param MongoId $accountId
     * @param array $openIds
     */
    public static function getLastByOpenIds($accountId, $openIds)
    {
        $condition = ['accountId' => $accountId, 'client.openId' => ['$in' => $openIds]];
        return self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
    }

    public static function getClient($accountId, $conversationId)
    {
        if (is_string($accountId)) {
            $accountId = new MongoId($accountId);
        }
        $condition = ['conversationId' => $conversationId, 'accountId' => $accountId];
        LogUtil::info(['ChatSession ' => 'getClient()', 'accountId' => $accountId, 'conversationId' => $conversationId], 'chatSession');
        $chatSession = static::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
        if (!empty($chatSession)) {
            return $chatSession->client;
        }
    }
}
