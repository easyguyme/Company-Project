<?php
namespace backend\modules\member\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberOrder.
 *
 * The followings are the available columns in collection 'statsMemberOrder':
 * @property MongoId    $_id
 * @property int        $consumerCount
 * @property int        $consumptionAmount
 * @property int        $transactionCount
 * @property int        $maxConsumptionTotal
 * @property int        $recentTransactionTotal
 * @property MongoDate  $createdAt
 * @property ObjectId   $accountId
 *
 **/

class StatsOrder extends PlainModel
{

    /**
     * Declares the name of the Mongo collection associated with statsMemberOrder.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsOrder';
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
            ['consumerCount', 'consumptionAmount', 'transactionCount', 'maxConsumptionTotal', 'recentTransactionTotal']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['consumerCount', 'consumptionAmount', 'transactionCount', 'maxConsumptionTotal', 'recentTransactionTotal']
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
                'consumerCount', 'consumptionAmount', 'transactionCount', 'maxConsumptionTotal', 'recentTransactionTotal'
            ]
        );
    }

    /**
     * Get by accountId
     * @param MongoId $accountId
     * @author Rex Chen
     */
    public static function getLatestByAccount($accountId)
    {
        return self::find()->where(['accountId' => $accountId])->orderBy(['createdAt' => SORT_DESC])->one();
    }
}
