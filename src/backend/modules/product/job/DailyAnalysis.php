<?php
namespace backend\modules\product\job;

use \MongoInt32;
use \MongoDate;
use backend\components\resque\SchedulerJob;
use backend\utils\LogUtil;
use backend\modules\product\models\PromotionCodeAnalysis as ModelPromotionCodeAnalysis;
use backend\utils\TimeUtil;

/**
* Job for analysis daily promotioncode
*/
class DailyAnalysis extends SchedulerJob
{
    /**
     * @args {"description": "Direct: Analysis daily promotion code "}
     */
    public function perform()
    {
        $yesterday = ModelPromotionCodeAnalysis::getTime(-1);
        $type = new MongoInt32(ModelPromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_EVERYDAY_PRIZE);
        $where = [
            'createdAt' => $yesterday,
            'type' => $type,
        ];

        $status = ModelPromotionCodeAnalysis::checkExistData($where);
        if ($status) {
            $yesterdayStamp = TimeUtil::today() - 24 * 3600;
            $yesterday = new MongoDate($yesterdayStamp);

            $campaignLogs = ModelPromotionCodeAnalysis::getCampaignLog();
            if (!empty($campaignLogs)) {
                $campaignData = ModelPromotionCodeAnalysis::createAnalysisData($campaignLogs, $type, $yesterday);
                if (false === ModelPromotionCodeAnalysis::batchInsert($campaignData)) {
                    LogUtil::error(['message' => 'Faild to create daily data', 'date' => date('Y-m-d H:i:s'), 'data' => json_encode($campaignData)], 'resque');
                }
            }
        } else {
            LogUtil::info(['message' => 'Daily analysis data is exists', 'date' => date('Y-m-d H:i:s')], 'resque');
        }
        return true;
    }
}
