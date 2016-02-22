<?php
namespace backend\modules\product\job;

use backend\components\resque\SchedulerJob;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\modules\product\models\StatsPromotionCodeAnalysis as ModelStatsPromotionCodeAnalysis;
use backend\modules\product\models\Campaign;
use backend\modules\product\models\CampaignLog;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use MongoDate;

/**
* Job for analysis promotioncode
*/
class StatsPromotionCodeAnalysis extends SchedulerJob
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['type'])) {
            $types = [
                PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE,
                PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL,
                PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE,
                PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE,
            ];
            foreach ($types as $type) {
                $yesterdayStamp = TimeUtil::today() - 24 * 3600;
                self::setData($type, $yesterdayStamp);
                LogUtil::info(['message' => 'run stats PromotionCodeAnalysis, type :' . $type], 'resque');
            }
        } else {
            $type = intval($args['type']);
            $yesterdayStamp = TimeUtil::today() - 24 * 3600;
            self::setData($type, $yesterdayStamp);
        }

        return true;
    }

    public static function setData($type, $yesterdayStamp, $createWhere = [], $searchTime = '')
    {
        $yesterday = new MongoDate($yesterdayStamp);

        if (empty($createWhere)) {
            $createWhere = PromotionCodeAnalysis::getCreateTime();
        }

        if (empty($searchTime)) {
            $searchTime = TimeUtil::today();
        }
        switch ($type) {
            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE:
                $productIds = CampaignLog::distinct('productId', $createWhere);

                $campaignLogs = self::_getCampaignLogs($productIds, $createWhere);

                if (!empty($campaignLogs)) {
                    self::_setStatsParticipate($campaignLogs, $yesterday);
                }
                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL:
                //get campaignlogs in yesterday
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
                $campaignLogs = PromotionCodeAnalysis::getMemberCampaignLog(false, $searchTime, $group, $secondGroup);
                //create datas
                if (!empty($campaignLogs)) {
                    $campaignData = [];
                    foreach ($campaignLogs as $key => $campaignLog) {
                        //get total the day before yesterday
                        $beforeYesterday = new MongoDate($searchTime - 2 * 24 * 3600);
                        $productId = $campaignLog['_id']['productId'];
                        $accountId = $campaignLog['_id']['accountId'];
                        $condition = [
                            'productId' => $productId,
                            'accountId' => $accountId,
                            'createdAt' => $beforeYesterday,
                            'type' => $type,
                        ];
                        $beforeYesterdayData = ModelStatsPromotionCodeAnalysis::findOne($condition);
                        if (empty($beforeYesterdayData)) {
                            $beforeYesterdayData['total'] = 0;
                        }

                        $condition = [
                            'accountId' => $accountId,
                            'productId' => $productId,
                        ];
                        $number = PromotionCodeAnalysis::checkMemberUnique($condition, $searchTime);
                        //subtract the member who is recorded before
                        $total = $beforeYesterdayData['total'] + $number;
                        $campaignLogs[$key]['total'] = $total;
                    }
                    $campaignData = ModelStatsPromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, $yesterday);
                    ModelStatsPromotionCodeAnalysis::batchInsert($campaignData);
                }
                //set the default value when the value is not exists
                $yesterdayStamp -= 3600 * 24;
                ModelStatsPromotionCodeAnalysis::setDefault($yesterdayStamp, $type);
                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE:
                $group =  [
                    '_id' => [
                        'accountId' => '$accountId',
                        'productId'=> '$productId'
                    ],
                    'total' => ['$sum' => 1]
                ];
                $campaignLogs = PromotionCodeAnalysis::getCampaignLog(false, $searchTime, $group);

                if (!empty($campaignLogs)) {
                    $campaignData = ModelStatsPromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, $yesterday);
                    ModelStatsPromotionCodeAnalysis::batchInsert($campaignData);
                }
                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE:
                $where = PromotionCodeAnalysis::getCreateTime($searchTime);
                $group = [
                    '_id' => [
                        'accountId' => '$accountId',
                        'memberId' => '$member.id',
                    ],
                ];
                $secondGroup = [
                    '_id' => [
                        'accountId' => '$_id.accountId',
                    ],
                    'total' => ['$sum' => 1]
                ];
                $campaignData = PromotionCodeAnalysis::getMemberAllTimes($where, $group, $secondGroup);
                $campaignData = ModelStatsPromotionCodeAnalysis::createAnalysisData($campaignData, $type, $yesterday);
                ModelStatsPromotionCodeAnalysis::batchInsert($campaignData);
                break;
        }
    }

    private static function _setStatsParticipate($campaignLogs, $yesterday)
    {
        //get total for take part in a campaign
        $campaignData = [];
        foreach ($campaignLogs as $data) {
            $key = (string)$data['_id']['productId'];
            if (isset($campaignData[$key])) {
                //to sum the total in every product in same campaign
                $campaignData[$key]['total'] += 1;
            } else {
                $productName = empty($data['_id']['productName']) ? '' : $data['_id']['productName'];
                $result = [
                    'productId' => $data['_id']['productId'],
                    'productName' => $productName,
                    'accountId'  => $data['_id']['accountId'],
                    'createdAt' => $yesterday,
                    'total' => 1,
                    'type' => PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE,
                ];
                $campaignData[$key] = $result;
            }
        }
        ModelStatsPromotionCodeAnalysis::batchInsert($campaignData);
        unset($datas, $campaignIds, $campaignData);
    }

    private static function _getCampaignLogs($productIds, $createWhere)
    {
        $campaignLogs = [];
        if (!empty($productIds)) {
            $where = array_merge($createWhere, ['productId' => ['$in' => $productIds]]);
            $campaignLogs = CampaignLog::getCollection()->aggregate(
                [
                    ['$match' => $where],
                    [
                        '$group' => [
                            '_id' => [
                                'productId'=> '$productId',
                                'productName' => '$productName',
                                'memberId' => '$member.id',
                                'accountId' => '$accountId',
                            ]
                        ]
                    ]
                ]
            );
        }
        return $campaignLogs;
    }
}
