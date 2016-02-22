<?php
namespace backend\modules\product\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use MongoDate;

/**
 * Model class for PromotionCodeAnalysis.
 * The followings are the available columns in collection 'PromotionCodeAnalysis':
 * @property MongoId    $_id
 * @property MongoDate  $createdAt
 * @property MongoId    $productId
 * @property string     $productName
 * @property MongoInt32 $total
 * @property MongoId    $campaignId
 * @property MongoInt32 $type
 * @property MongoId    $accountId
 **/
class PromotionCodeAnalysis extends PlainModel
{
    const PROMOTION_CODE_ANALYSIS_PARTICIPATE = 1;
    const PROMOTION_CODE_ANALYSIS_TOTAL = 2;
    const PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE = 3;
    const PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE = 4;
    const PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE_TITLE = 'product_statistics_daily_total';

    /**
    * Declares the name of the Mongo collection associated with PromotionCodeAnalysis.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'promotionCodeAnalysis';
    }

    /**
    * Returns the list of all attribute names of PromotionCodeAnalysis.
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'campaignId', 'total', 'productName', 'type']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['productId', 'campaignId', 'total', 'productName', 'type']
        );
    }

    /**
    * Returns the list of all rules of PromotionCodeAnalysis.
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
                'productId', 'campaignId', 'total', 'productName', 'type',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                }
            ]
        );
    }

    /**
     * get member info from campaignlog by create time
     * @return array
     * @param $where, array, search condition
     * @param $group, array, first group the search data
     * @param $secondGroup, array, the second group the search data
     */
    public static function getMemberAllTimes($where, $group = [], $secondGroup = [])
    {
        if (empty($group)) {
            $group = [
                '_id' => [
                    'campaignId'=>'$campaignId',
                    'accountId' => '$accountId',
                    'memberId' => '$member.id',
                ],
            ];
            $secondGroup = [
                '_id' => [
                    'campaignId'=>'$_id.campaignId',
                    'accountId' => '$_id.accountId',
                ],
                'total' => ['$sum' => 1]
            ];
        }

        $campaignLogs = CampaignLog::getCollection()->aggregate(
            [
                ['$match' => $where],
                ['$group' => $group],
                ['$group' => $secondGroup]
            ]
        );
        return $campaignLogs;
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
        $where = ['campaignId' => $params['campaignId'], 'accountId' => $accountId, 'createdAt' => ['$gte' => $params['startDate'], '$lt' => $params['endDate']]];
        $results = [];
        foreach ($types as $type) {
            $series = $info = $productNameData = $statsProduct = [];
            $k = 0;
            $where['type'] = new \MongoInt32($type);
            $datas = PromotionCodeAnalysis::find()->where($where)->orderBy(['createdAt' => 'asc', 'productName' => 'asc'])->all();

            if (!empty($datas)) {
                foreach ($datas as $data) {
                    if (self::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE == $type) {
                        $data['productName'] = self::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE_TITLE;
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
                    //record productName
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


    /**
     * get the time from now,and return mongodate
     * @param $days,int,how many days from now
     */
    public static function getTime($days)
    {
        //to keep the yesterday time is (Y-m-d 00:00:00)
        $yesterday = TimeUtil::today() + $days * 24 * 3600;
        return new MongoDate($yesterday);
    }

    /**
     * check the data has been created
     * @param $where,array
     */
    public static function checkExistData($where)
    {
        $analysisData = PromotionCodeAnalysis::findOne($where);
        return empty($analysisData) ? true : false;
    }

    /**
     * get the campaign log
     * @param $total,boolean,true means to get the all campaign logs,false means to get a part of logs
     * @param $today,timestamp,get log at what time
     */
    public static function getCampaignLog($total = false, $today = '', $group = [])
    {
        if (empty($group)) {
            $group =  [
                '_id' => [
                    'campaignId'=>'$campaignId',
                    'accountId' => '$accountId',
                    'productId'=> '$productId'
                ],
                'total' => ['$sum' => 1]
            ];
        }

        $createWhere = self::_getCreateTime($total, $today);
        $campaignLogs = CampaignLog::getCollection()->aggregate(
            [
                ['$match' => $createWhere],
                ['$group' => $group]
            ]
        );
        return $campaignLogs;
    }

    private static function _getCreateTime($total, $today)
    {
        if (empty($today)) {
            $today = TimeUtil::today();
        }

        if ($total) {
            $createWhere = ['createdAt' => ['$lt' => new MongoDate($today)]];
        } else {
            $createWhere = self::getCreateTime($today);
        }
        return $createWhere;
    }

    /**
     * get the campaign log
     * @param $total,boolean,true means to get the all campaign logs,false means to get a part of logs
     * @param $today,timestamp,get log at what time
     */
    public static function getMemberCampaignLog($total = false, $today = '', $group = [], $secondGroup = [])
    {
        $createWhere = self::_getCreateTime($total, $today);

        if (empty($group)) {
            $group = [
                '_id' => [
                    'campaignId'=>'$campaignId',
                    'accountId' => '$accountId',
                    'productId'=> '$productId',
                    'memberId' => '$member.id'
                ],
            ];

            $secondGroup = [
                '_id' => [
                    'campaignId'=>'$_id.campaignId',
                    'accountId' => '$_id.accountId',
                    'productId'=> '$_id.productId',
                ],
                'total' => ['$sum' => 1]
            ];
        }
        $campaignLogs = CampaignLog::getCollection()->aggregate(
            [
                ['$match' => $createWhere],
                ['$group' => $group],
                ['$group' => $secondGroup],
            ]
        );
        return $campaignLogs;
    }

    /**
     * get the number of member who do not take part in this campaign before
     * @param $condition,array
     * @param $create,array
     */
    public static function checkMemberUnique($condition, $create)
    {
        $where = [
            'createdAt' => ['$gte' => new MongoDate($create - 24 * 3600), '$lt' => new MongoDate($create)],
        ];
        $where = array_merge($where, $condition);
        $members = CampaignLog::distinct('member.id', $where);
        //set default value
        $number = count($members);
        if (!empty($members)) {
            $where['createdAt'] = ['$lt' => new MongoDate($create - 24 * 3600)];
            $where['member.id'] = ['$in' => $members];
            $members = CampaignLog::distinct('member.id', $where);
            $number -= count($members);
        }
        return $number;
    }

    /**
     * create the condition to describe when to create the data
     * @param $now,timestamp
     */
    public static function getCreateTime($now = '')
    {
        if (empty($now)) {
            $now = TimeUtil::today();
        }
        $yesterday = $now - 3600 * 24;
        $now = new MongoDate($now);
        $yesterday = new MongoDate($yesterday);
        $createWhere = ['createdAt' => ['$gte' => $yesterday, '$lt' => $now]];
        return $createWhere;
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
                'campaignId' => $data['_id']['campaignId'],
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
        $campaignLogs = self::getMemberCampaignLog(true, $now);
        $yesterday = new MongoDate($yesterday);
        //sum every campaign and product
        if (!empty($campaignLogs)) {
            $defaultData = [];
            foreach ($campaignLogs as &$campaignLog) {
                //check data not exist
                $where = [
                    'campaignId' => $campaignLog['_id']['campaignId'],
                    'type'      => $type,
                    'accountId' => $campaignLog['_id']['accountId'],
                    'productId' => $campaignLog['_id']['productId'],
                    'createdAt' => $today,
                ];
                $data = PromotionCodeAnalysis::findOne($where);
                if (empty($data)) {
                    //create today data,to get yesterday data
                    $where['createdAt'] = $yesterday;
                    $data = PromotionCodeAnalysis::findOne($where);

                    $total = 0;
                    if (!empty($data)) {
                        $total = $data['total'];
                    }
                    $campaignLog['total'] = $total;

                    $defaultData = self::createAnalysisData([$campaignLog], $type, $today);
                    PromotionCodeAnalysis::batchInsert($defaultData);
                }
            }
        }
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
}
