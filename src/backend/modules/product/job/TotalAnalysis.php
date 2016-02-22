<?php
namespace backend\modules\product\job;

use \MongoInt32;
use \MongoDate;
use backend\components\resque\SchedulerJob;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\product\models\PromotionCodeAnalysis as ModelPromotionCodeAnalysis;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;

/**
* Job for analysis daily promotioncode
*/
class TotalAnalysis extends SchedulerJob
{
    /**
     * @args {"description": "Direct: Analysis daily promotion code "}
     */
    public function perform()
    {
        $yesterday = ModelPromotionCodeAnalysis::getTime(-1);
        $type = new MongoInt32(ModelPromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL);
        $where = [
            'createdAt' => $yesterday,
            'type' => $type,
        ];

        $status = ModelPromotionCodeAnalysis::checkExistData($where);
        if ($status) {
            $yesterdayStamp = TimeUtil::today() - 24 * 3600;
            $yesterday = new MongoDate($yesterdayStamp);

            //get campaignlogs in yesterday
            $campaignLogs = ModelPromotionCodeAnalysis::getMemberCampaignLog();
            //create datas
            if (!empty($campaignLogs)) {
                $campaignData = [];
                foreach ($campaignLogs as $key => $campaignLog) {
                    //get total the day before yesterday
                    $beforeYesterday = new MongoDate(TimeUtil::today() - 2 * 24 * 3600);
                    $productId = $campaignLog['_id']['productId'];
                    $campaignId = $campaignLog['_id']['campaignId'];
                    $accountId = $campaignLog['_id']['accountId'];
                    $condition = [
                        'productId' => $productId,
                        'campaignId' => $campaignId,
                        'accountId' => $accountId,
                        'createdAt' => $beforeYesterday,
                        'type' => $type,
                    ];
                    $beforeYesterdayData = ModelPromotionCodeAnalysis::findOne($condition);
                    if (empty($beforeYesterdayData)) {
                        $beforeYesterdayData['total'] = 0;
                    }

                    $condition = [
                        'campaignId' => $campaignId,
                        'accountId' => $accountId,
                        'productId' => $productId,
                    ];
                    $number = ModelPromotionCodeAnalysis::checkMemberUnique($condition, TimeUtil::today());
                    //subtract the member who is recorded before
                    $total = $beforeYesterdayData['total'] + $number;
                    $campaignLogs[$key]['total'] = $total;
                }
                $campaignData = ModelPromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, $yesterday);

                if (false === ModelPromotionCodeAnalysis::batchInsert($campaignData)) {
                    LogUtil::error(['message' => 'Faild to create daily data', 'date' => date('Y-m-d H:i:s'), 'data' => json_encode($campaignData)], 'resque');
                }
            }
            //set the default value when the value is not exists
            $yesterdayStamp -= 3600 * 24;
            ModelPromotionCodeAnalysis::setDefault($yesterdayStamp, $type);
        } else {
            LogUtil::info(['message' => 'Total analysis data is exists', 'date' => date('Y-m-d H:i:s')], 'resque');
        }
        return true;
    }
}
