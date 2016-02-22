<?php
namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use MongoId;
use MongoDate;

/**
 * Model class for statsMemberPropTradeCodeQuarterly.
 *
 * The followings are the available columns in collection 'statsMemberPropTradeCodeQuarterly':
 * @property MongoId $_id
 * @property String $propId
 * @property String $propValue
 * @property String $productId
 * @property int $total
 * @property String $year
 * @property String $quarter
 * @property ObjectId $accountId
 *
 **/

class StatsMemberPropTradeCodeQuarterly extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberPropTradeCodeQuarterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberPropTradeCodeQuarterly';
    }

    /**
     * Returns the list of all attribute names of statsMemberPropTradeCodeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'total', 'year', 'quarter']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['propId', 'propValue', 'total', 'year', 'quarter']
        );
    }

    /**
     * Returns the list of all rules of statsMemberPropTradeCodeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['propId', 'propValue', 'total', 'year', 'quarter'], 'required'],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberPropTradeCodeQuarterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'propId', 'propValue', 'total', 'year', 'quarter'
            ]
        );
    }

    public static function generateByYearAndQuarter($propertyId, $accountId, $createdTime)
    {
        $accountId = new MongoId($accountId);
        $today = new MongoDate($createdTime);
        $tomorrow = new MongoDate(strtotime('+1 day', $createdTime));

        $condition = [
            'accountId' => $accountId,
            'createdAt' => ['$gte' => $today, '$lt' => $tomorrow],
        ];

        $campaignLogDailys = StatsMemberCampaignLogDaily::getCollection()->aggregate(
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$memProperty.' . $propertyId,
                        'code' => '$code',
                        'year' => '$year',
                        'quarter' => '$quarter',
                    ]
                ],
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$_id.propValue',
                        'year' => '$_id.year',
                        'quarter' => '$_id.quarter',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        );

        $rows = [];
        if (!empty($campaignLogDailys)) {
            foreach ($campaignLogDailys as $item) {
                $item['_id']['propValue'] = !empty($item['_id']['propValue']) ? $item['_id']['propValue'] : [];
                $where = [
                    'propId' => $propertyId,
                    'propValue' => $item['_id']['propValue'],
                    'year' => $item['_id']['year'],
                    'quarter' => $item['_id']['quarter'],
                    'accountId' => $accountId
                ];
                $record = self::findOne($where);
                if (!empty($record)) {
                    $total = self::getTotalWithCode($item, $propertyId, $accountId);
                    if ($total > 0) {
                        $record->total = $total;
                        $record->save(true, ['total']);
                    }
                } else {
                    $rows[] = [
                        'propId' => $propertyId,
                        'propValue' => $item['_id']['propValue'],
                        'total' => $item['total'],
                        'year' => $item['_id']['year'],
                        'quarter' => $item['_id']['quarter'],
                        'accountId' => $accountId,
                    ];
                }
            }
        }
        return self::batchInsert($rows);
    }

    /**
     * get total number base on code and property
     */
    public static function getTotalWithCode($item, $propertyId, $accountId)
    {
        $where = [
            'memProperty.' . $propertyId => $item['_id']['propValue'],
            'year' => $item['_id']['year'],
            'quarter' => $item['_id']['quarter'],
            'accountId' => $accountId
        ];
        $summary = StatsMemberCampaignLogDaily::getCollection()->aggregate(
            [
                '$match' => $where,
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$memProperty.' . $propertyId,
                        'code' => '$code',
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'propValue' => '$_id.propValue',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        );
        if (empty($summary)) {
            return 0;
        } else {
            return $summary[0]['total'];
        }
    }

    public static function getByYearAndQuarter($year, $quarter, $accountId)
    {
        $condition = ['accountId' => $accountId, 'year'=> $year, 'quarter' => intval($quarter)];
        return self::findAll($condition);
    }

    /**
     * deal with data before export data
     */
    public static function preProcessData($condition)
    {
        $export = [];
        $datas = self::findAll($condition);
        if (!empty($datas)) {
            foreach ($datas as &$data) {
                if (empty($data['propValue'])) {
                    unset($data);
                } else {
                    $export[] = $data;
                }
            }
            unset($data, $datas);
        }
        return $export;
    }
}
