<?php
namespace backend\modules\member\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberGrowthMonthly.
 *
 * The followings are the available columns in collection 'statsMemberGrowthMonthly':
 * @property MongoId    $_id
 * @property int        $totalNew
 * @property int        $totalActive
 * @property int        $totalInactive
 * @property int        $month
 * @property MongoDate  $createdAt
 * @property ObjectId   $accountId
 *
 **/

class StatsMemberGrowthMonthly extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberGrowthMonthly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberGrowthMonthly';
    }

    /**
     * Returns the list of all attribute names of statsMemberGrowthMonthly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalNew', 'totalActive', 'totalInactive', 'month']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalNew', 'totalActive', 'totalInactive', 'month']
        );
    }

    /**
     * Returns the list of all rules of statsMemberGrowthMonthly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['totalNew', 'totalActive', 'totalInactive'], 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberGrowthMonthly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'totalNew', 'totalActive', 'totalInactive', 'month',
                'createdAt' => function($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    public static function getByMonthAndAccount($accountId, $month)
    {
        $condition = ['accountId' => $accountId, 'month' => $month];
        return self::findOne($condition);
    }

    public static function getByMonth($accountId, $start, $end)
    {
        $condition = [
            'accountId' => $accountId,
            'month' => ['$gte' => $start, '$lte' => $end]
        ];

        return self::findAll($condition);
    }

    public static function preProcessData($condition)
    {
        $stats = self::findAll($condition);
        $monthActiveMap = [];
        foreach ($stats as $stat) {
            $monthActiveMap[$stat->month] = $stat->totalActive;
        }
        $startDate = $condition['month']['$gte'];
        $endDate = $condition['month']['$lte'];
        $dateTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $data = [];
        while ($dateTime <= $endTime) {
            $date = date('Y-m', $dateTime);
            $data[] = [
                'month' => $date,
                'number' => empty($monthActiveMap[$date]) ? 0 : $monthActiveMap[$date]
            ];
            $dateTime = strtotime('+1 month', $dateTime);
        }

        return $data;
    }
}
