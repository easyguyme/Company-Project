<?php
namespace backend\models;

use MongoId;
use MongoDate;
use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\modules\product\models\Product;

/**
 * Model class for statsCampaignProductCodeQuarterly.
 *
 * The followings are the available columns in collection 'statsCampaignProductCodeQuarterly':
 * @property MongoId $_id
 * @property String $productId
 * @property String $productName
 * @property int $total
 * @property String $year
 * @property String $quarter
 * @property ObjectId $accountId
 *
 **/

class StatsCampaignProductCodeQuarterly extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsCampaignProductCodeQuarterly.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsCampaignProductCodeQuarterly';
    }

    /**
     * Returns the list of all attribute names of statsCampaignProductCodeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'productName', 'total', 'year', 'quarter']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'productName', 'total', 'year', 'quarter']
        );
    }

    /**
     * Returns the list of all rules of statsCampaignProductCodeQuarterly.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['productId', 'productName', 'total', 'year', 'quarter'], 'required'],
                ['total', 'integer'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsCampaignProductCodeQuarterly.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'productId', 'productName', 'total', 'year', 'quarter'
            ]
        );
    }

    /**
     * @param $accountId,string
     * @param $createdTime, int(timestamp)
     */
    public static function generateByYearAndQuarter($accountId, $createdTime)
    {
        $accountId = new MongoId($accountId);
        $today = new MongoDate($createdTime);
        $tomorrow = new MongoDate(strtotime('+1 day', $createdTime));

        $condition = [
            'accountId' => $accountId,
            'createdAt' => ['$gte' => $today, '$lt' => $tomorrow],
        ];

        $campaignLogDailys = StatsMemberCampaignLogDaily::getCollection()->aggregate([
            [
                '$match' => $condition,
            ],
            [
                '$group' => [
                    '_id' => [
                        'productId' => '$productId',
                        'year' => '$year',
                        'quarter' => '$quarter',
                    ],
                    'total' => [
                        '$sum' => 1
                    ]
                ]
            ]
        ]);

        $rows = [];
        if (!empty($campaignLogDailys)) {
            foreach ($campaignLogDailys as $item) {
                $where = [
                    'productId' => $item['_id']['productId'],
                    'year' => $item['_id']['year'],
                    'quarter' => $item['_id']['quarter'],
                ];
                $productCodeLog = self::findOne($where);

                if (!empty($productCodeLog)) {
                    //summary this product
                    $total = self::getTotalWithProductId($where);
                    if ($total > 0) {
                        $productCodeLog->total = $total;
                        $productCodeLog->save(true, ['total']);
                    }
                } else {
                    $productName = '';
                    $product = Product::findByPK($item['_id']['productId']);
                    if (!empty($product)) {
                        $productName = $product->name;
                    }
                    $rows[] = [
                        'productId' => $item['_id']['productId'],
                        'productName' => $productName,
                        'total' => $item['total'],
                        'year' => $item['_id']['year'],
                        'quarter' => $item['_id']['quarter'],
                        'accountId' => $accountId
                    ];
                }
            }
            unset($item, $campaignLogDailys);
        }
        return self::batchInsert($rows);
    }

    /**
     * get
     */
    public static function getTotalWithProductId($where)
    {
        $summary = StatsMemberCampaignLogDaily::getCollection()->aggregate(
            [
                '$match' => $where,
            ],
            [
                '$group' => ['_id' => ['productId' => '$productId'], 'total' => ['$sum' => 1]],
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
        return self::findAll($condition);
    }
}
