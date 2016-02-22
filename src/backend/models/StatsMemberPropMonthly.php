<?php

namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberPropMonthly.
 *
 * The followings are the available columns in collection 'statsMemberPropMonthly':
 * @property MongoId $_id
 * @property String $propId
 * @property String $propValue
 * @property int $total
 * @property String $month '2015-01'
 * @property ObjectId $accountId
 *
 **/

class StatsMemberPropMonthly extends PlainModel
{
    const NOT_SELECTED = '未选';
    /**
     * Declares the name of the Mongo collection associated with statsMemberPropMonthly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberPropMonthly';
    }

    /**
     * Returns the list of all attribute names of statsMemberPropMonthly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'total', 'month']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'total', 'month']
        );
    }

    /**
     * Returns the list of all rules of statsMemberPropMonthly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['propId', 'propValue', 'total', 'month'], 'required'],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberPropMonthly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'propId', 'propValue', 'total', 'month'
            ]
        );
    }

    public function preProcessData($condition)
    {
        $stats = self::findAll($condition);
        $monthMap = [];
        $operates = [];
        foreach ($stats as $stat) {
            if (!is_array($monthMap[$stat->month])) {
                $monthMap[$stat->month] = [];
            }
            $monthMap[$stat->month][$stat->propValue] = $stat->total;
            $operates[] = $stat->propValue;
            $operates = array_unique($operates);
        }
        $startDate = $condition['month']['$gte'];
        $endDate = $condition['month']['$lte'];
        $dateTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $data = [];
        while ($dateTime <= $endTime) {
            $date = date('Y-m', $dateTime);
            foreach ($operates as $operate) {
                $item = [
                    'month' => $date,
                    'operate' => empty($operate) ? self::NOT_SELECTED : $operate,
                    'number' => 0
                ];

                if (!empty($monthMap[$date][$operate])) {
                    $item['number'] = empty($monthMap[$date][$operate]) ? 0 : $monthMap[$date][$operate];
                }

                $data[] = $item;
            }
            $dateTime = strtotime('+1 month', $dateTime);
        }

        return $data;
    }
}
