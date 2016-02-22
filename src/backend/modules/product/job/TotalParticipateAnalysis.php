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
* Job for analysis total participate
*/
class TotalParticipateAnalysis extends SchedulerJob
{
    /**
     * @args {"description": "Direct: Analysis total participate"}
     */
    public function perform()
    {
        $yesterday = ModelPromotionCodeAnalysis::getTime(-1);
        $type = new MongoInt32(ModelPromotionCodeAnalysis::PROMOTION_CODE_ANALYSIS_TOTAL_PARTICIPATE);
        $where = [
            'createdAt' => $yesterday,
            'type' => $type,
        ];

        $status = ModelPromotionCodeAnalysis::checkExistData($where);
        if ($status) {
            $yesterdayStamp = TimeUtil::today() - 24 * 3600;
            $yesterday = new MongoDate($yesterdayStamp);

            $where = ModelPromotionCodeAnalysis::getCreateTime();
            $campaignData = ModelPromotionCodeAnalysis::getMemberAllTimes($where);
            $campaignData = ModelPromotionCodeAnalysis::createAnalysisData($campaignData, $type, $yesterday);

            if (false === ModelPromotionCodeAnalysis::batchInsert($campaignData)) {
                LogUtil::error(['message' => 'Faild to create daily data', 'date' => date('Y-m-d H:i:s'), 'data' => json_encode($campaignData)], 'resque');
            }
        } else {
            LogUtil::info(['message' => 'Total participate analysis data is exists', 'date' => date('Y-m-d H:i:s')], 'resque');
        }
        return true;
    }
}
