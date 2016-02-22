<?php
namespace backend\modules\helpdesk\models;

use Yii;
use MongoDate;
use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;

/**
 * Model class for Statistics.
 *
 * The followings are the available columns in collection 'statistics':
 * @property MongoId   $_id
 * @property int    $totalUser
 * @property int    $totalConversation
 * @property int    $totalMessage
 * @property ObjectId  $accountId
 * @property MongoDate $createdAt
 *
 * @author Mike Wang
 */
class Statistics extends PlainModel
{

    const USER_DAILY_CONNECT = 'user-daily-connect';

    /**
     * Declares the name of the Mongo collection associated with statistics.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'conversationStatistics';
    }

    /**
     * Returns the list of all attribute names of statistics.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalUser', 'totalConversation', 'totalMessage']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalUser', 'totalConversation', 'totalMessage']
        );
    }

    /**
     * Returns the list of all rules of statistics.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            []
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into FAQ.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'totalUser', 'totalConversation', 'totalMessage',
                 'createdAt' => function($model) {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d');
                }
            ]
        );
    }

    public static function incrTotalUser($accountId)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(TimeUtil::today())], 'accountId' => $accountId];
        return static::updateAll(['$inc' => ['totalUser' => 1]], $condition);
    }

    public static function incrTotalConversation($accountId)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(TimeUtil::today())], 'accountId' => $accountId];
        return static::updateAll(['$inc' => ['totalConversation' => 1]], $condition);
    }

    public static function incrTotalMessage($accountId)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(TimeUtil::today())], 'accountId' => $accountId];
        return static::updateAll(['$inc' => ['totalMessage' => 1]], $condition);
    }

    public static function updateRecord($accountId, $totalUser, $totalConversation, $totalMessage)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(TimeUtil::today())], 'accountId' => $accountId];
        $update = ['$inc' => ['totalUser' => $totalUser, 'totalConversation' => $totalConversation, 'totalMessage' => $totalMessage]];
        return static::updateAll($update, $condition);
    }

    public static function createRecord($accountId)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(TimeUtil::today())], 'accountId' => $accountId];
        $isExist = static::count($condition);
        if ($isExist) {
            return true;
        }
        $statistics = new self;
        $statistics->totalUser = 0;
        $statistics->totalConversation = 0;
        $statistics->totalMessage = 0;
        $statistics->accountId = $accountId;
        return $statistics->save();
    }

    public static function getStatsCount($condition)
    {
        $pipeline = [
            ['$match' => $condition],
            [
                '$group' => [
                    '_id'                => null,
                    'totalUser'          => ['$sum' => '$totalUser'],
                    'totalConversation'  => ['$sum' => '$totalConversation'],
                    'totalMessage'       => ['$sum' => '$totalMessage'],
                ]
            ]
        ];
        return self::getCollection()->aggregate($pipeline);

    }

    public static function statsUser($clientId, $accountId)
    {
        $redis = Yii::$app->cache->redis;
        $key = self::USER_DAILY_CONNECT . '-' . (string)$accountId;

        // If the key exist, set the expire time if not set.
        if ($redis->exists($key)) {
            $ttl = $redis->ttl($key);
            if ($ttl < 0) {
                //Calculate the seconds left from now to tomorrow
                $tomorrow = strtotime(date('Y-m-d')) + 24 * 60 * 60;
                $expire = $tomorrow - time();
                //Set key expire time(second)
                $redis->expire($key, $expire);
            }
        }
        // If the field exist in ket, update the value of the field
        if ($redis->hexists($key, $clientId)) {
            $total = $redis->hget($key, $clientId) + 1;
            $redis->hset($key, $clientId, $total);
            return;
        }
        $redis->hset($key, $clientId, 1);

        // First connect today, update the totalUser of conversationStatistics
        static::incrTotalUser($accountId);
    }
}
