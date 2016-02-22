<?php
namespace backend\modules\product\models;

use Yii;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\components\ActiveDataProvider;
use backend\components\PlainModel;
use backend\models\Channel;
use backend\models\Goods;

/**
 * Model class for GoodsExchangeLog.
 *
 * The followings are the available columns in collection 'GoodsExchangeLog':
 * @property MongoId    $_id
 * @property array      $goods [{id, productId, sku, productName, count}]
 * @property MongoId    $memberId
 * @property string     $memberName
 * @property string     $telephone
 * @property int        $usedScore
 * @property int        $expectedScore
 * @property int        $count
 * @property array      $usedFrom
 * @property string     $address
 * @property string     $receiveMode
 * @property boolean    $isDelivered
 * @property MongoId    $accountId
 * @property bool       $isRemoved
 * @property Date       $accountId
 **/
class GoodsExchangeLog extends PlainModel
{
    const USED_FROM_WECHAT = 'wechat';
    const USED_FROM_WEIBO = 'weibo';
    const USED_FROM_OFFLINE = 'offline';

    const OFFLINE_EXCHANGE = 'offline_exchange';
    const MONGO_ID_LENGTH = 24;

    /**
     * Declares the name of the Mongo collection associated with GoodsExchangeLog.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'goodsExchangeLog';
    }

    /**
     * Returns the list of all attribute names of GoodsExchangeLog.
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
            [
                'goods', 'memberId', 'memberName',
                'telephone', 'usedScore', 'expectedScore',
                'count', 'usedFrom', 'isRemoved',
                'address', 'postcode', 'isDelivered',
                'receiveMode',
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'goods', 'memberId', 'memberName', 'telephone',
                'usedScore', 'expectedScore', 'count',
                'usedFrom', 'isRemoved', 'address',
                'postcode', 'isDelivered', 'receiveMode',
            ]
        );
    }

    /**
     * Returns the list of all rules of GoodsExchangeLog.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['usedScore', 'expectedScore'], 'number', 'min' => 0, 'integerOnly' => true],
                ['count', 'number', 'min' => 0, 'integerOnly' => true],
                ['isRemoved', 'default', 'value' => false],
                ['isDelivered', 'default', 'value' => false],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into GoodsExchangeLog.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'goods' => function () {
                    $goods = $this->goods;
                    foreach ($goods as &$item) {
                        $item['id'] = (string) $item['id'];
                        $item['productId'] = (string) $item['productId'];
                    }
                    return $goods;
                },
                'memberId' => function () {
                    return (string) $this->memberId;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'memberName', 'telephone', 'usedScore',
                'count', 'usedFrom', 'expectedScore',
                'address', 'postcode', 'isDelivered',
                'receiveMode',
            ]
        );
    }

    public static function search($params, $accountId)
    {
        $query = self::find();
        $condition = self::createCondition($params, $accountId);

        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);
        return new ActiveDataProvider(['query' => $query]);
    }

    /**
     * support to get condtion to search log used in api for index function and export function
     */
    public static function createCondition($params, $accountId)
    {
        $condition = ['accountId' => $accountId];

        if (array_key_exists('key', $params) && '' != $params['key']) {
            $condition = self::createKeyWordCondition($params['key'], $condition);
        }

        $usedTime = [];
        if (isset($params['startTime'])) {
            $usedTime['createdAt']['$gte'] = new \MongoDate(TimeUtil::ms2sTime($params['startTime']));
        }
        if (isset($params['endTime'])) {
            $usedTime['createdAt']['$lte'] = new \MongoDate(TimeUtil::ms2sTime($params['endTime']));
        }
        $condition = array_merge($condition, $usedTime);

        $usedScore = [];
        if (isset($params['usedScoreMin'])) {
            $usedScore['usedScore']['$gte'] = $params['usedScoreMin'] + 0;
        }
        if (isset($params['usedScoreMax'])) {
            $usedScore['usedScore']['$lte'] = $params['usedScoreMax'] + 0;
        }
        $condition = array_merge($condition, $usedScore);

        if (!empty($params['memberId'])) {
            $condition['memberId'] = new \MongoId($params['memberId']);
        }
        if (!empty($params['channelId'])) {
            $condition['usedFrom.id'] = $params['channelId'];
        }
        if (isset($params['isRemoved'])) {
            $condition['isRemoved'] = $params['isRemoved'];
        }
        if (!empty($params['usedFrom'])) {
            $condition = array_merge($condition, $params['usedFrom']);
        }

        if (isset($params['isDelivered'])) {
            $condition = self::createIsDeliveredCondition($params['isDelivered'], $condition);
        }

        if (isset($params['receiveMode'])) {
            $condition = self::createReceiveModeCondition($params['receiveMode'], $condition);
        }

        // this condition must in the end,because this condition return is ['and', $condition, $channelCondition], you can see andWhere()
        if (!empty($params['accounts'])) {
            $condition = self::createChannelCondition($condition, $params['accounts']);
        }

        return $condition;
    }

    public static function createReceiveModeCondition($receiveMode, $condition)
    {
        $params = explode(',', $receiveMode);
        return array_merge($condition, ['receiveMode' => ['$in' => $params]]);
    }

    public static function createIsDeliveredCondition($isDelivered, $condition)
    {
        //isDelivered only two value, true or false
        $params = explode(',', $isDelivered);
        if (2 == count($params)) {
            return $condition;
        } else {
            $isDelivered = 0 == $params[0] ? false : true;
            return  array_merge($condition, ['isDelivered' => $isDelivered]);
        }

    }

    public static function createChannelCondition($condition, $accountIdList)
    {
        $accounts = explode(',', $accountIdList);
        $channelIds = [];
        foreach ($accounts as $account) {
            $channelIds[] = $account;
        }

        $channelCondition = [
            '$or' => [
                ['usedFrom.id' => ['$in' => $channelIds]],
                ['usedFrom.type' => ['$in' => $channelIds]]
            ]
        ];
        return ['and', $condition, $channelCondition];
    }

    public static function createKeyWordCondition($key, $condition)
    {
        $key = StringUtil::regStrFormat(trim($key));
        $keyReg = new \MongoRegex("/$key/i");
        $search = [
            '$or' => [
                ['memberName' => $keyReg],
                ['goods.productName' => $keyReg],
                ['goods.sku' => $keyReg],
                ['telephone' => $keyReg]
            ]
        ];
        return array_merge($condition, $search);
    }

    public static function preProcessExportData($data, $baseData)
    {
        $printData = [];

        foreach ($data as $index => $value) {
            $goodsCount = [];
            $goods = [];
            if (!empty($value['goods'])) {
                foreach ($value['goods'] as $good) {
                    $goods[] = empty($good['productName']) ? '' : ($good['productName'] . '*' .$good['count']);
                }
            }

            $printData[$index] = [
                'id' => (string) $value['_id'],
                'memberName' => $value['memberName'],
                'telephone' => $value['telephone'],
                'address' => $value['address'],
                'postcode' => $value['postcode'],
                'expectedScore' => $value['expectedScore'],
                'usedScore' => $value['usedScore'],
                'goods' => implode(", ", $goods),
                'createdAt' => MongodbUtil::MongoDate2String($value['createdAt']),
                'channel' => Yii::t('common', $value['usedFrom']['type']),
            ];
        }
        return $printData;
    }

    public static function getLastExpressByMember($memberId)
    {
        $condition = ['memberId' => $memberId, 'receiveMode' => Goods::RECEIVE_MODE_EXPRESS];
        return self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
    }
}
