<?php
namespace backend\modules\product\job;

use backend\modules\resque\components\ResqueUtil;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\modules\product\models\Product;
use backend\modules\product\models\CampaignLog;
use backend\utils\TimeUtil;

/**
* Job for update analysis promotioncode
*/
class PromotionCodeAnalysisUpdate
{

    public function perform()
    {
        $args = $this->args;

        if (empty($args['beginTime']) || empty($args['endTime']) || empty($args['type'])) {
            ResqueUtil::log(['error' => 'missing params', 'args' => $args]);
            return false;
        }

        $beginTime = strtotime($args['beginTime']) + 3600 * 24;
        $endTime = strtotime($args['endTime']);
        if ($endTime > time()) {
            $endTime = strtotime(date('Y-m-d', time()));
        }
        $endTime += 3600 * 24;

        $type = new \MongoInt32($args['type']);
        switch ($args['type']) {
            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE:
                //delete data and create data
                for ($t = $beginTime; $t <= $endTime; $t += 3600 * 24) {
                    $where = ['createdAt' => new \MongoDate($t - 3600 * 24), 'type' => $type];
                    PromotionCodeAnalysis::deleteAll($where);

                    //create data begin
                    $createWhere = PromotionCodeAnalysis::getCreateTime($t);
                    $campaignIds = CampaignLog::distinct('campaignId', $createWhere);

                    $campaignLogs = [];
                    if (!empty($campaignIds)) {
                        $where = array_merge($createWhere, ['campaignId' => ['$in' => $campaignIds]]);
                        $campaignLogs = CampaignLog::getCollection()->aggregate(
                            [
                                ['$match' => $where],
                                [
                                    '$group' => [
                                        '_id' => [
                                            'campaignId'=>'$campaignId',
                                            'accountId' => '$accountId',
                                            'productId'=> '$productId',
                                            'memberId' => '$member.id',
                                        ]
                                    ]
                                ]
                            ]
                        );
                    }
                    if (!empty($campaignLogs)) {
                        //get total for take part in a campaign
                        $campaignData = [];
                        foreach ($campaignLogs as $data) {
                            $campaignId = $data['_id']['campaignId'];
                            $key = (string)$campaignId . (string)$data['_id']['productId'];
                            if (isset($campaignData[$key])) {
                                //to sum the total in every product in same campaign
                                $campaignData[$key]['total'] += 1;
                            } else {
                                $product = Product::findByPk($data['_id']['productId']);
                                $productName = empty($product['name']) ? '' : $product['name'];
                                $result = [
                                    'productId' => $data['_id']['productId'],
                                    'productName' => $productName,
                                    'campaignId' => $campaignId,
                                    'accountId'  => $data['_id']['accountId'],
                                    'createdAt' =>  new \MongoDate($t - 3600 * 24),
                                    'total' => 1,
                                    'type' => $type,
                                ];
                                $campaignData[$key] = $result;
                            }
                        }
                        PromotionCodeAnalysis::batchInsert($campaignData);
                        unset($datas, $campaignIds, $campaignData);
                    }
                }

                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL:
                //delete data and create data
                for ($t = $beginTime; $t <= $endTime; $t += 3600 * 24) {
                    $where = ['createdAt' => new \MongoDate($t - 3600 * 24), 'type' => $type];
                    PromotionCodeAnalysis::deleteAll($where);
                    //get campaignlogs in yesterday
                    $campaignLogs = PromotionCodeAnalysis::getMemberCampaignLog(false, $t);
                    //create datas
                    $yesterday =  new \MongoDate($t - 2 * 24 * 3600);
                    if (!empty($campaignLogs)) {
                        $campaignData = [];
                        foreach ($campaignLogs as $key => $campaignLog) {
                            //get total the day yesterday
                            $productId = $campaignLog['_id']['productId'];
                            $campaignId = $campaignLog['_id']['campaignId'];
                            $accountId = $campaignLog['_id']['accountId'];
                            $condition = [
                                'productId' => $productId,
                                'campaignId' => $campaignId,
                                'accountId' => $accountId,
                                'createdAt' => $yesterday,
                                'type' => $type,
                            ];
                            $yesterdayData = PromotionCodeAnalysis::findOne($condition);

                            if (empty($yesterdayData)) {
                                $yesterdayData['total'] = 0;
                            }
                            $condition = [
                                'campaignId' => $campaignId,
                                'accountId' => $accountId,
                                'productId' => $productId,
                            ];
                            $number = PromotionCodeAnalysis::checkMemberUnique($condition, $t);
                            //subtract the member who is recorded before
                            $total = $yesterdayData['total'] + $number;
                            $campaignLogs[$key]['total'] = $total;
                        }
                        $campaignData = PromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, new \MongoDate($t - 24 * 3600));
                        PromotionCodeAnalysis::batchInsert($campaignData);
                    }
                    //set the default value when the value is not exists
                    PromotionCodeAnalysis::setDefault($t - 2 * 24 * 3600, $type);
                }
                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE:
                for ($t = $beginTime; $t <= $endTime; $t += 3600 * 24) {
                    //delete data
                    $where = ['createdAt' => new \MongoDate($t - 3600 * 24), 'type' => $type];
                    PromotionCodeAnalysis::deleteAll($where);

                    $campaignLogs = PromotionCodeAnalysis::getCampaignLog(false, $t);
                    if (!empty($campaignLogs)) {
                        $campaignData = PromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, new \MongoDate($t - 3600 * 24));
                        PromotionCodeAnalysis::batchInsert($campaignData);
                    }
                }
                break;

            case PromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE:
                for ($t = $beginTime; $t <= $endTime; $t += 3600 * 24) {
                    //delete data
                    $where = ['createdAt' => new \MongoDate($t - 3600 * 24), 'type' => $type];
                    PromotionCodeAnalysis::deleteAll($where);
                    unset($where);
                    $where = PromotionCodeAnalysis::getCreateTime($t);
                    $campaignLogs = PromotionCodeAnalysis::getMemberAllTimes($where);

                    if (!empty($campaignLogs)) {
                        $campaignData = PromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, new \MongoDate($t - 3600 * 24));
                        PromotionCodeAnalysis::batchInsert($campaignData);
                    }
                }
                break;
        }

        return true;
    }
}
