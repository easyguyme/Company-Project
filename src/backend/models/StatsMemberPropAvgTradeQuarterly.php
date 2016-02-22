<?php

namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberPropAvgTradeQuarterly.
 *
 * The followings are the available columns in collection 'statsMemberPropAvgTradeQuarterly':
 * @property MongoId $_id
 * @property String $propName
 * @property String $propValue
 * @property String $productName
 * @property int $avg
 * @property String $year
 * @property String $quarter
 * @property ObjectId $accountId
 *
 **/

class StatsMemberPropAvgTradeQuarterly extends PlainModel
{
    const NOT_SELECTED = '未选';

    /**
     * Declares the name of the Mongo collection associated with statsMemberPropAvgTradeQuarterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberPropAvgTradeQuarterly';
    }

    /**
     * Returns the list of all attribute names of statsMemberPropAvgTradeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'avg', 'year', 'quarter']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'avg', 'year', 'quarter']
        );
    }

    /**
     * Returns the list of all rules of statsMemberPropAvgTradeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['propId', 'propValue', 'avg', 'year', 'quarter'], 'required'],
                ['avg', 'double'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberPropAvgTradeQuarterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'propId', 'propValue', 'avg', 'year', 'quarter'
            ]
        );
    }

    public static function getByPropAndDate($accountId, $propId, $propValue, $year, $quarter)
    {
        $condition = [
            'accountId' => $accountId,
            'propId' => $propId,
            'propValue' => $propValue,
            'year' => $year,
            'quarter' => $quarter
        ];

        return self::findOne($condition);
    }

    public static function getByYearAndAccount($accountId, $year)
    {
        $condition = ['accountId' => $accountId, 'year' => $year];
        return self::findAll($condition);
    }

    public static function preProcessData($condition)
    {
        $statsQuater = self::findAll($condition);

        $avgData = [];
        foreach ($statsQuater as $stats) {
            if (empty($stats->propValue)) {
                $stats->propValue = self::NOT_SELECTED;
            }
            $avgData[$stats->propValue][$stats->quarter] = $stats->avg;
        }

        $querters = [1, 2, 3, 4];
        $data = [];
        foreach ($querters as $querter) {
            $date = 'Q' . $querter;
            foreach ($avgData as $propValue => $avgs) {
                $data[] = [
                    'quarter' => $date,
                    'operate' => $propValue,
                    'number' => empty($avgs[$querter]) ? 0 : (float) sprintf("%.2f", $avgs[$querter])
                ];
            }
        }

        return $data;
    }
}
