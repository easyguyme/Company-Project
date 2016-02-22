<?php
namespace backend\modules\product\job;

use backend\modules\product\models\StatsPromotionCodeAnalysis as ModelStatsPromotionCodeAnalysis;
use backend\modules\product\job\StatsPromotionCodeAnalysis;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use MongoDate;
use Yii;

/**
* Job for update analysis promotioncode
*/
class StatsPromotionCodeAnalysisUpdate
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['beginTime']) || empty($args['endTime']) || empty($args['type'])) {
            LogUtil::error(['message' => 'missing params when update stats promotion code analysis', 'args' => $args], 'resque');
            return false;
        }

        $beginTime = strtotime($args['beginTime']) + 3600 * 24;
        $endTime = strtotime($args['endTime']);
        if ($endTime > time()) {
            $endTime = strtotime(date('Y-m-d', time()));
        }
        $endTime += 3600 * 24;

        $type = intval($args['type']);

        for ($t = $beginTime; $t <= $endTime; $t += 3600 * 24) {
            $where = ['createdAt' => new MongoDate($t - 3600 * 24), 'type' => $type];
            ModelStatsPromotionCodeAnalysis::deleteAll($where);

            $createWhere = PromotionCodeAnalysis::getCreateTime($t);
            $yesterdayStamp = $t - 3600 * 24;
            StatsPromotionCodeAnalysis::setData($type, $yesterdayStamp, $createWhere, $t);
        }
        return true;
    }
}
