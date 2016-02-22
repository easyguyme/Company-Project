<?php
/**
 * Base Controller for module uhkklp
 * */

namespace backend\modules\uhkklp\controllers;

use backend\components\Controller;

class BaseController extends Controller
{
    const NOT_SELECTED = '未选';
    /**
     * Format stats data with date
     * @param  Object $models        AR object list
     * @param  string $categoryKey   category key in database, sucha as 'month', 'quarter'
     * @param  string $numKey        number key in database, sucha as 'avg', 'total'
     * @param  array  $dateCondition It looks like the followings:
     * ~~~
     * $dateCondition = [
     *      'dateFormat' => 'Y-m',
     *      'dateDiff' => '+1 month',
     *      'startDate' => $startDate,
     *      'endDate' => $endDate
     *  ];
     * ~~~
     */
    protected function _formatStatsWithDate($models, $categoryKey, $numKey, $dateCondition)
    {
        extract($dateCondition);
        $result = [];
        foreach ($models as $model) {
            if (empty($model->propValue)) {
                $model->propValue = self::NOT_SELECTED;
            }
            if (empty($result[$model->propValue])) {
                $result[$model->propValue] = [];
            }
            $data[$model->propValue][$model->$categoryKey] = $model->$numKey;
        }
        $data[$categoryKey] = [];

        $result = [];
        foreach ($data as $key => $totalItems) {
            $result[$key] = [];
            for ($date = $startDate; $date <= $endDate;) {
                if ($key === $categoryKey) {
                    array_push($result[$categoryKey], $date);
                } else {
                    if (empty($totalItems[$date])) {
                        array_push($result[$key], 0);
                    } else {
                        array_push($result[$key], $totalItems[$date]);
                    }
                }
                $date = date($dateFormat, strtotime($dateDiff, strtotime($date)));
            }
        }
        $categories = $result[$categoryKey];
        unset($result[$categoryKey]);
        $result = ['data' => $result, $categoryKey => $categories];

        return $result;
    }

    /**
     * Format stats data with category
     * @param  Object $models        AR object list
     * @param  string $category      category key in database, sucha as 'productId'
     * @param  string $categoryValue category value in database, sucha as 'productName'
     * @param  string $numKey        number key in database, sucha as 'avg', 'total'
     */
    protected function _formatStatsWithCategory($models, $category, $categoryValue, $numKey)
    {
        $result = [];
        $categoryKeys = [];
        foreach ($models as $model) {
            if (empty($model->propValue)) {
                $model->propValue = self::NOT_SELECTED;
            }
            $model->$category = (string) $model->$category;
            if (empty($result[$model->propValue])) {
                $result[$model->propValue] = [];
            }
            $data[$model->propValue][$model->$category] = $model->$numKey;
            $categoryKeys[$model->$category] = $model->$categoryValue;
        }
        $data[$categoryValue] = [];

        $result = [];
        foreach ($data as $key => $totalItems) {
            $result[$key] = [];
            foreach ($categoryKeys as $categoryKey => $value) {
                if ($key === $categoryValue) {
                    array_push($result[$categoryValue], $value);
                } else {
                    if (empty($totalItems[$categoryKey])) {
                        array_push($result[$key], 0);
                    } else {
                        array_push($result[$key], $totalItems[$categoryKey]);
                    }
                }
            }
        }

        $categories = $result[$categoryValue];
        unset($result[$categoryValue]);
        $result = ['data' => $result, $categoryValue => $categories];

        return $result;
    }


}
