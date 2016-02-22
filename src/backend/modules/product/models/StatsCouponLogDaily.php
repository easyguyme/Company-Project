<?php
namespace backend\modules\product\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsCouponLogDaily.
 *
 * The followings are the available columns in collection 'statsCouponLogDaily':
 * @property MongoId    $_id
 * @property MongoId    $couponId
 * @property int        $recievedNum
 * @property int        $redeemedNum
 * @property MongoDate  $date
 * @property MongoDate  $createdAt
 * @property MonogId    $accountId
 **/
class StatsCouponLogDaily extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsCouponLogDaily.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsCouponLogDaily';
    }

    /**
     * Returns the list of all attribute names of statsCouponLogDaily.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'couponId', 'recievedNum', 'redeemedNum', 'date'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'couponId', 'recievedNum', 'redeemedNum', 'date'
            ]
        );
    }

    /**
     * Returns the list of all rules of statsCouponLogDaily.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [

            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsCouponLogDaily.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'couponId', 'recievedNum', 'redeemedNum',
                'date' => function () {
                    return MongodbUtil::MongoDate2String($this->date);
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                }
            ]
        );
    }

    /**
    * Get recievedNum, recievedNum, deletedNum by Id.
    * @param $couponId MongoId
    */
    public static function getCouponLogTotalStats($couponId)
    {
        $condition = ['couponId' => $couponId];
        $pipeline = [
            ['$match' => $condition],
            [
                '$group' => [
                    '_id' => ['couponId' => '$couponId', 'accountId' => '$accountId'],
                    'totalRecievedNum' => ['$sum' => '$recievedNum'],
                    'totalRedeemedNum' => ['$sum' => '$redeemedNum'],
                ]
            ],
            [
                '$project' => [
                    'accountId' => '$_id.accountId',
                    'couponId' => '$_id.couponId',
                    '_id' => 0,
                    'totalRecievedNum' => 1,
                    'totalRedeemedNum' => 1,
                ]

            ]
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
    * Get couponLog by Id.
    * @param $couponId MongoId
    * @param $startTime string
    * @param $endTime string
    */
    public static function getCouponLogStats($couponId, $startTime = '', $endTime = '')
    {
        $condition = ['couponId' => $couponId];

        if (!empty($startTime)) {
            $condition['date']['$gte'] = $startTime;
        }
        if (!empty($endTime)) {
            $condition['date']['$lte'] = $endTime;
        }

        $pipeline = [
            ['$match' => $condition]
        ];
        return self::getCollection()->aggregate($pipeline);
    }
}
