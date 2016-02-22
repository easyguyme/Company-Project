<?php
namespace backend\models;

use Yii;
use MongoRegex;
use MongoId;
use MongoDate;
use backend\components\BaseModel;
use backend\utils\StringUtil;
use backend\utils\MongoDate2String;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\exceptions\InvalidParameterException;
use backend\components\ActiveDataProvider;

/**
 * Model class for staff.
 * The followings are the available columns in collection 'Order':
 * @property MongoId $_id
 * @property MongoId $storeId
 * @property String  $orderNumber
 * @property String  $expectedPrice
 * @property String  $totalPrice
 * @property array   $staff['id', 'name']
 * @property array   $consumer['id', 'name', 'phone', 'avatar']
 * @property array   $storeGoods[['id', 'name', 'pictures', 'sku', 'price', 'count', 'totalPrice']]
 * @proprty  string  $status
 * @property string  $payWay
 * @property MongoDate $operateTime
 * @property MongoDate $createdAt
 * @property MongoId $accountId
 **/

class Order extends BaseModel
{
    const ORDER_STATUS_FINISHED = 'finished';
    const ORDER_STATUS_WAITING = 'pending';
    const ORDER_STATUS_CANCEL = 'canceled';

    /**
    * Declares the name of the Mongo collection associated with Order.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'order';
    }

    /**
    * Returns the list of all attribute names of order.
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
            ['storeId', 'orderNumber', 'expectedPrice', 'totalPrice', 'staff', 'consumer', 'storeGoods', 'status', 'payWay', 'remark', 'operateTime']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['storeId', 'orderNumber', 'expectedPrice', 'totalPrice', 'staff', 'consumer', 'storeGoods', 'status', 'payWay', 'remark', 'operateTime']
        );
    }
    /**
    * Returns the list of all rules of order.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['status', 'default', 'value' => self::ORDER_STATUS_WAITING],
                ['operateTime', 'default', 'value' => new MongoDate()],
                ['storeId', 'toMongoId'],
            ]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into order.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'id' => function ($model) {
                    return (string)$model->_id;
                },
                'storeId' => function ($model) {
                    return (string)$model->storeId;
                },
                'expectedPrice' => function ($model) {
                    return sprintf('%.2f', $model->expectedPrice);
                },
                'totalPrice' => function ($model) {
                    return sprintf('%.2f', $model->totalPrice);
                },
                'staff' => function ($model) {
                    $staff = $model->staff;
                    $staff['id'] .= '';
                    return $staff;
                },
                'consumer' => function ($model) {
                    $consumer = $model->consumer;
                    if (empty($consumer['id']) || false === MongoId::isValid($consumer['id'])) {
                        unset($consumer['id']);
                    }
                    if (empty($consumer['phone'])) {
                        unset($consumer['phone']);
                    }
                    return $consumer;
                },
                'storeGoods' => function ($model) {
                    $storeGoods = $model->storeGoods;
                    if (!empty($storeGoods)) {
                        foreach ($storeGoods as &$storeGood) {
                            $storeGood['id'] .= '';
                            $storeGood['price'] = sprintf('%.2f', $storeGood['price']);
                            $storeGood['totalPrice'] = sprintf('%.2f', $storeGood['totalPrice']);
                        }
                    }
                    return $storeGoods;
                },
                'status', 'payWay', 'orderNumber', 'remark',
                'operateTime' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->operateTime, 'Y-m-d H:i:s');
                },
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt, 'Y-m-d H:i:s');
                },
            ]
        );
    }

    public function extraFields()
    {
        return array_merge(
            parent::fields(),
            [
                'store' => function () {
                    return Store::findByPk($this->storeId);
                }
            ]
        );
    }

    public static function search($params, $accountId)
    {
        $query = Order::find();

        $condition = self::createCondition($params, $accountId);
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);
        return new ActiveDataProvider(['query' => $query]);
    }

    public static function getStoreGoods($params, $accountId, $page, $perPage)
    {
        $condition = self::createCondition($params, $accountId);
        $pipeline = [
            ['$match' => $condition],
            ['$unwind' => '$storeGoods'],
            ['$sort' => ['createdAt' => 1]],
            ['$skip' => ($page - 1) * $perPage],
            ['$limit' => $perPage],
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    public static function countStoreGoods($params, $accountId)
    {
        $condition = self::createCondition($params, $accountId);
        $pipeline = [
            ['$match' => $condition],
            ['$unwind' => '$storeGoods'],
            ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]],
        ];
        return self::getCollection()->aggregate($pipeline)[0]['count'];
    }

    /**
     * create condition for search
     */
    public static function createCondition($params, $accountId)
    {
        $condition = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        //order number
        if (!empty($params['orderNumber'])) {
            $orderNumber = StringUtil::regStrFormat(trim($params['orderNumber']));
            $condition['orderNumber'] = new MongoRegex("/$orderNumber/i");
        }
        if (!empty($params['memberId'])) {
            $condition['consumer.id'] = $params['memberId'];
        }
        //order status
        if (!empty($params['status'])) {
            $status = explode(',', $params['status']);
            $condition['status'] = ['$in' => $status];
        }
        //store id
        if (!empty($params['storeId'])) {
            $condition['storeId'] = new \MongoId($params['storeId']);
        }
        //createdAt
        // After run new MongoDate, The time can lost accuracy, so it will plus 1 or subtract 1.
        if (!empty($params['beginCreatedAt'])) {
            $beginCreatedAt = TimeUtil::ms2sTime($params['beginCreatedAt']) - 1;
            $condition['createdAt']['$gt'] = new MongoDate($beginCreatedAt);
        }

        if (!empty($params['endCreatedAt'])) {
            $endCreatedAt = TimeUtil::ms2sTime($params['endCreatedAt']) + 1;
            $condition['createdAt']['$lt'] = new MongoDate($endCreatedAt);
        }
        //price
        if (!empty($params['minAmount'])) {
            $condition['totalPrice']['$gte'] = floatval($params['minAmount']);
        }
        if (!empty($params['maxAmount'])) {
            $condition['totalPrice']['$lte'] = floatval($params['maxAmount']);
        }
        //staff info
        if (!empty($params['staff'])) {
            $staffName = StringUtil::regStrFormat(trim($params['staff']));
            $staffName = new MongoRegex("/$staffName/i");
            $condition['staff.name'] = $staffName;
        }
        //member info
        if (!empty($params['member'])) {
            $member = StringUtil::regStrFormat(trim($params['member']));
            $condition['consumer.name'] = new MongoRegex("/$member/i");
        }
        return $condition;
    }

    /**
     * check the status whether in the status which defined in backend
     * $status,string
     */
    public static function checkOrderStatus($status)
    {
        $orderStatus = [self::ORDER_STATUS_FINISHED, self::ORDER_STATUS_CANCEL];

        if (!in_array($status, $orderStatus)) {
            return false;
        }
        return true;
    }

    /**
     * Get all member stats by account and operatTime
     * @param MongoId $accountId
     * @param MongoDate $operateTimeFrom
     * @param MongoDate $operateTimeTo
     * @author Rex Chen
     */
    public static function getMemberStats($accountId, $operateTimeFrom = null, $operateTimeTo = null)
    {
        $condition = ['accountId' => $accountId, 'consumer.id' => new MongoRegex('/^[A-Za-z0-9]{24}$/'), 'status' => self::ORDER_STATUS_FINISHED];
        if (!empty($operateTimeFrom)) {
            $condition['operateTime']['$gte'] = $operateTimeFrom;
        }
        if (!empty($operateTimeTo)) {
            $condition['operateTime']['$lt'] = $operateTimeTo;
        }
        $keys = ['consumer.id' => true];
        $initial = ['consumptionAmount' => 0.0, 'transactionCount' => 0.0, 'maxConsumption' => 0.0];
        $reduce = 'function(doc, prev) {
                        prev.consumptionAmount += doc.totalPrice;
                        prev.transactionCount++;
                        if (prev.maxConsumption < doc.totalPrice) {
                            prev.maxConsumption = doc.totalPrice;
                        }
                    }';
        $options = [
            'condition' => $condition,
        ];
        return self::getCollection()->group($keys, $initial, $reduce, $options);
    }

    /**
     * Get transaction count
     * @param MongoId $accountId
     * @param MongoDate $operateTimeFrom
     * @param MongoDate $operateTimeTo
     * @author Rex Chen
     */
    public static function getMemberTransactionCount($accountId, $operateTimeFrom, $operateTimeTo)
    {
        $condition = [
            'accountId' => $accountId,
            'consumer.id' => new MongoRegex('/^[A-Za-z0-9]{24}$/'),
            'status' => self::ORDER_STATUS_FINISHED,
            'operateTime' => [
                '$lt' => $operateTimeTo,
                '$gte' => $operateTimeFrom,
            ]
        ];
        $pipeline = [
            ['$match' => $condition],
            ['$group' => ['_id' => '$consumer.id', 'count' => ['$sum' => 1]]]
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
     * Get latest recode by consumerId
     * @param MongoId $accountId
     * @param string $consumerId
     * @author Rex Chen
     */
    public static function getLastByConsumerId($accountId, $consumerId)
    {
        $condition = ['accountId' => $accountId, 'consumer.id' => $consumerId, 'status' => self::ORDER_STATUS_FINISHED];
        return self::find()->where($condition)->orderBy(['operateTime' => SORT_DESC])->one();
    }
}
