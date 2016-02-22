<?php
namespace backend\modules\member\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberGrowthQuarterly.
 *
 * The followings are the available columns in collection 'statsMemberGrowthQuarterly':
 * @property MongoId    $_id
 * @property int        $totalNew
 * @property int        $totalActive
 * @property int        $totalInactive
 * @property int        $year
 * @property int        $quarter
 * @property MongoDate  $createdAt
 * @property ObjectId   $accountId
 *
 **/

class StatsMemberGrowthQuarterly extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberGrowthQuarterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberGrowthQuarterly';
    }

    /**
     * Returns the list of all attribute names of statsMemberGrowthQuarterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalNew', 'totalActive', 'totalInactive', 'year', 'quarter']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['totalNew', 'totalActive', 'totalInactive', 'year', 'quarter']
        );
    }

    /**
     * Returns the list of all rules of statsMemberGrowthQuarterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['totalNew', 'totalActive', 'totalInactive', 'year', 'quarter'], 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberGrowthQuarterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'totalNew', 'totalActive', 'totalInactive', 'year', 'quarter',
                'createdAt' => function($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    public static function getByQuarterAndAccount($accountId, $year, $quarter)
    {
        $condition = [
            'accountId' => $accountId,
            'year' => $year,
            'quarter' => $quarter
        ];

        return self::findOne($condition);
    }

    public static function preProcessData($condition)
    {
        $stats = self::findOne($condition);

        $data = [];
        if (!empty($stats)) {
            $data[] = [
                'active' => $stats->totalActive,
                'inactive' => $stats->totalInactive,
                'new' => $stats->totalNew
            ];
        }

        return $data;
    }
}
