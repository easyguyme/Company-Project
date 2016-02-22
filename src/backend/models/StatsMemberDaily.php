<?php

namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for StatsMemberDaily.
 *
 * The followings are the available columns in collection 'statsMemberDaily':
 * @property MongoId $_id
 * @property string $date
 * @property String $origin
 * @property String $originName
 * @property int $total
 * @property MongoDate $createdAt
 * @property ObjectId $accountId
 *
 **/

class StatsMemberDaily extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberDaily.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberDaily';
    }

    /**
     * Returns the list of all attribute names of statsMemberDaily.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['date', 'origin', 'originName', 'total']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['date', 'origin', 'originName', 'total']
        );
    }

    /**
     * Returns the list of all rules of statsMemberDaily.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['date', 'origin', 'total'], 'required'],
                ['origin', 'in', 'range' => self::$origins],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberDaily.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'date', 'origin', 'originName', 'total',
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    public static function getByDateAndOriginInfo($date, $origin, $originName, $accountId)
    {
        $condition = [
            'accountId' => $accountId,
            'date' => $date,
            'origin' => $origin,
            'originName' => $originName
        ];
        return self::findOne($condition);
    }

    public static function getMonthData($month)
    {
        $pipeline = [
            ['$match' => ['date' => ['$lte' => $month . '-31', '$gte' => $month . '-01']]],
            [
                '$group' => [
                    '_id' => ['origin' => '$origin', 'originName' => '$originName', 'accountId' => '$accountId'],
                    'total' => ['$sum' => '$total']
                ]
            ]
        ];
        $result = self::getCollection()->aggregate($pipeline);

        return $result;
    }
}
