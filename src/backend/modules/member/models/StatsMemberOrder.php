<?php
namespace backend\modules\member\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberOrder.
 *
 * The followings are the available columns in collection 'statsMemberOrder':
 * @property MongoId    $_id
 * @property string     $consumerId
 * @property int        $consumptionAmount
 * @property int        $transactionCount
 * @property int        $maxConsumption
 * @property int        $recentTransactionCount
 * @property MongoDate  $createdAt
 * @property ObjectId   $accountId
 *
 **/

class StatsMemberOrder extends PlainModel
{

    /**
     * Declares the name of the Mongo collection associated with statsMemberOrder.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberOrder';
    }

    /**
     * Returns the list of all attribute names of statsMemberOrder.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['consumerId', 'consumptionAmount', 'transactionCount', 'maxConsumption', 'recentTransactionCount']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['consumerId', 'consumptionAmount', 'transactionCount', 'maxConsumption', 'recentTransactionCount']
        );
    }

    /**
     * Returns the list of all rules of statsMemberOrder.
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
     * The default implementation returns the names of the columns whose values have been populated into statsMemberOrder.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'consumerId', 'consumptionAmount', 'transactionCount', 'maxConsumption', 'recentTransactionCount',
            ]
        );
    }

    /**
     * Get by consumerId
     * @param MongoId $accountId
     * @param string $memberId
     * @author Rex Chen
     */
    public static function getByConsumerId($accountId, $consumerId)
    {
        return self::findOne(['accountId' => $accountId, 'consumerId' => $consumerId]);
    }

    /**
     * Get stats by accountId
     * @param MongoId $accountId
     * @author Rex Chen
     */
    public static function getStatsByAccount()
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$accountId',
                    'consumerCount' => ['$sum' => 1],
                    'consumptionAmount' => ['$sum' => '$consumptionAmount'],
                    'transactionCount' => ['$sum' => '$transactionCount'],
                    'maxConsumptionTotal' => ['$sum' => '$maxConsumption'],
                    'recentTransactionTotal' => ['$sum' => '$recentTransactionCount'],
                ],
            ],
            [
                '$project' => [
                    'accountId' => '$_id',
                    'consumerCount' => 1,
                    'consumptionAmount' => 1,
                    'transactionCount' => 1,
                    'maxConsumptionTotal' => 1,
                    'recentTransactionTotal' => 1,
                    '_id' => 0
                ]
            ]
        ];
        return self::getCollection()->aggregate($pipeline);
    }
}
