<?php
namespace backend\modules\product\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\modules\product\models\PromotionCodeAnalysis;
use MongoDate;

/**
 * Model class for statsPromotionCodeAnalysis.
 * The followings are the available columns in collection 'statsPromotionCodeAnalysis':
 * @property MongoId    $_id
 * @property MongoDate  $createdAt
 * @property string     $productName
 * @property MongoInt32 $total
 * @property MongoId    $productId
 * @property MongoInt32 $type
 * @property MongoId    $accountId
 **/
class StatsPromotionCodeAnalysis extends PlainModel
{
    const ANALYSIS_PARTICIPATE = 1;
    const ANALYSIS_TOTAL = 2;
    const EVERYDAY_PRIZE = 3;
    const PARTICIPATE = 4;
    const PARTICIPATE_TITLE = 'product_statistics_daily_total';

    /**
    * Declares the name of the Mongo collection associated with statsPromotionCodeAnalysis.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'statsPromotionCodeAnalysis';
    }

    /**
    * Returns the list of all attribute names of statsPromotionCodeAnalysis.
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'total', 'productName', 'type']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'total', 'productName', 'type']
        );
    }

    /**
    * Returns the list of all rules of statsPromotionCodeAnalysis.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(parent::rules(), []);
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into PromotionCodeAnalysis.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'productId', 'total', 'productName', 'type',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                }
            ]
        );
    }

   /**
     * get the analysis base on the type
     * 1.to get date in categories
     * 2.to get productName in series.name
     * 3.to get total in series.data
     * @param $type.int or array,the type of analysis
     * @param $accountId,mongoId
     * @param $params,array
     */
    public static function getAnalysisData($types, $accountId, $params)
    {
        $where = [
            'accountId' => $accountId,
            'createdAt' => ['$gte' => $params['startDate'],
            '$lt' => $params['endDate']]
        ];

        $results = [];

        foreach ($types as $type) {
            $series = $info = $productNameData = $statsProduct = [];
            $k = 0;
            $where['type'] = intval($type);
            $datas = StatsPromotionCodeAnalysis::find()->where($where)->orderBy(['createdAt' => 'asc', 'productName' => 'asc'])->all();
            if (!empty($datas)) {
                foreach ($datas as $data) {
                    if (self::PARTICIPATE == $type) {
                        $data['productName'] = self::PARTICIPATE_TITLE;
                    }
                    $analysisDate = MongodbUtil::MongoDate2String($data['createdAt'], 'Y-m-d');

                    //record the date
                    if (empty($info['categories'])) {
                        $info['categories'] = [$analysisDate];
                    } else {
                        if (!in_array($analysisDate, $info['categories'])) {
                            $info['categories'][] = $analysisDate;
                        }
                    }
                    //record campaignName
                    if (empty($productNameData)) {
                        $productNameData[] = $data['productName'];
                    } else {
                        if (!in_array($data['productName'], $productNameData)) {
                            $productNameData[] = $data['productName'];
                        }
                    }
                    //record productName => [$date => $total]
                    $statsProduct[$data['productName']][$analysisDate] = $data['total'];
                }

                foreach ($info['categories'] as $categories) {
                    foreach ($productNameData as $key => $productName) {
                        $series[$key]['name'] = $productName;
                        $total = 0;
                        if (isset($statsProduct[$productName][$categories])) {
                            $total = $statsProduct[$productName][$categories];
                        }
                        $series[$key]['data'][] = $total;
                    }
                }
                $info['series'] = $series;
            }
            $results[$type] = $info;
            unset($info);
        }
        unset($series, $data, $datas);
        //merge type value 1 and 4,the type is 4 that is the total for 1,so it is only one data
        if (!empty($results[1]['series']) && !empty($results[4]['series'][0])) {
            array_push($results[1]['series'], $results[4]['series'][0]);
            unset($results[4]);
        }
        return $results;
    }

    public static function preProcessData($datas, $args)
    {
        $row = [];
        if (!empty($datas)) {
            $headers = $args['header'];
            $row['productName'] = $datas[0]['productName'];
            foreach ($headers as $key => $header) {
                foreach ($datas as $data) {
                    $createdAt = date('Y-m-d', strtotime($data['createdAt']['$date']));
                    if ($key == $createdAt && !isset($row[$createdAt])) {
                        $row[$createdAt] = $data['total']['$numberLong'];
                        break;
                    }
                }
                if (!isset($row[$key])) {
                    $row[$key] = 0;
                }
            }
        }
        return $row;
    }

      /**
     * create the structure for promorioncodeanalysis
     * @param $datas,array,source data
     * @param $type,mongoInt,the type for ananlysis
     * @param $yesterday,mongodate, create time
     */
    public static function createAnalysisData($datas, $type, $yesterday)
    {
        $campaignData = [];
        foreach ($datas as $data) {
            if (!empty($data['_id']['productId'])) {
                $productId = $data['_id']['productId'];
                $product = Product::findByPk($data['_id']['productId']);
                $productName = empty($product['name']) ? 'unknow' : $product['name'];
            } else {
                $productId = $productName = '';
            }

            $campaignData[] = [
                'createdAt' => $yesterday,
                'total' => $data['total'],
                'type' => $type,
                'productId' => $productId,
                'productName' => $productName,
                'accountId' => $data['_id']['accountId'],
            ];
        }
        return $campaignData;
    }

     /**
     * create a default data
     * @param $accountIds,array
     * @param $yesterday,int
     * @param $type.mongoInt32
     */
    public static function setDefault($yesterday, $type)
    {
        $now = $yesterday + 3600 * 24;
        $today = new MongoDate($now);
        //get all compaign id
        $group = [
            '_id' => [
                'accountId' => '$accountId',
                'productId'=> '$productId',
                'memberId' => '$member.id'
            ],
        ];

        $secondGroup = [
            '_id' => [
                'accountId' => '$_id.accountId',
                'productId'=> '$_id.productId',
            ],
            'total' => ['$sum' => 1]
        ];
        $campaignLogs = PromotionCodeAnalysis::getMemberCampaignLog(true, $now, $group, $secondGroup);
        $yesterday = new MongoDate($yesterday);
        //sum every campaign and product
        if (!empty($campaignLogs)) {
            $defaultData = [];
            foreach ($campaignLogs as &$campaignLog) {
                //check data not exist
                $where = [
                    'type'      => $type,
                    'accountId' => $campaignLog['_id']['accountId'],
                    'productId' => $campaignLog['_id']['productId'],
                    'createdAt' => $today,
                ];
                $data = self::findOne($where);
                if (empty($data)) {
                    //create today data,to get yesterday data
                    $where['createdAt'] = $yesterday;
                    $data = self::findOne($where);

                    $total = 0;
                    if (!empty($data)) {
                        $total = $data['total'];
                    }
                    $campaignLog['total'] = $total;

                    $defaultData = self::createAnalysisData([$campaignLog], $type, $today);
                    self::batchInsert($defaultData);
                }
            }
        }
    }
}
