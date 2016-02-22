<?php
namespace backend\modules\product\job;

use \MongoInt32;
use \MongoDate;
use backend\components\resque\SchedulerJob;
use backend\modules\product\models\PromotionCodeAnalysis as ModelPromotionCodeAnalysis;
use backend\modules\product\models\Product;
use backend\modules\product\models\CampaignLog;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;

/**
* Job for analysis daily promotioncode
*/
class ParticipateAnalysis extends SchedulerJob
{
    /**
     * @args {"description": "Direct: Analysis participate promotion code "}
     */
    public function perform()
    {
        $yesterday = ModelPromotionCodeAnalysis::getTime(-1);
        $type = new MongoInt32(ModelPromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_PARTICIPATE);
        $where = [
            'createdAt' => $yesterday,
            'type' => $type,
        ];

        $status = ModelPromotionCodeAnalysis::checkExistData($where);
        if ($status) {
            $yesterdayStamp = TimeUtil::today() - 24 * 3600;
            $yesterday = new MongoDate($yesterdayStamp);

            $createWhere = ModelPromotionCodeAnalysis::getCreateTime();
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
                                    'memberId' => '$member.id',
                                    'accountId' => '$accountId',
                                    'productId'=> '$productId'
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
                            'createdAt' => $yesterday,
                            'total' => 1,
                            'type' => $type,
                        ];
                        $campaignData[$key] = $result;
                    }
                }
                if (false === ModelPromotionCodeAnalysis::batchInsert($campaignData)) {
                    LogUtil::error(['message' => 'Faild to create daily data', 'date' => date('Y-m-d H:i:s'), 'data' => json_encode($campaignData)], 'resque');
                }
                unset($datas, $campaignIds, $campaignData);
            }
        } else {
            LogUtil::info(['message' => 'Participate analysis data is exists', 'date' => date('Y-m-d H:i:s')], 'resque');
        }
        return true;
    }
}
