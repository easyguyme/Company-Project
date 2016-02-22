<?php
namespace backend\modules\product\job;

use backend\modules\product\models\CouponLog;
use backend\modules\product\job\StatsCouponLogDaily;
use backend\modules\product\models\StatsCouponLogDaily as ModelStatsCouponLogDaily;
use backend\utils\LogUtil;

class StatsCouponLogDailyUpdate
{
    /**
     * @args {"description": "Direct: update Stats of coupon"}
     */
    public function perform()
    {
        $args = $this->args;

        $startTime = strtotime($args['startTime']);
        $endTime = strtotime($args['endTime']);
        $current = strtotime(date('Y-m-d'));

        if ($endTime > $current) {
            $endTime = $current;
        }

        for ($t = $startTime; $t <= $endTime; $t += 3600 * 24) {
            $dateStr = date('Y-m-d', $t);
            ModelStatsCouponLogDaily::deleteAll(['date' => $dateStr]);
            $stats = CouponLog::getStats($dateStr);
            if (!empty($stats)) {
                StatsCouponLogDaily::createStatsCouponLog($dateStr, $stats);
            }
            LogUtil::info(['message' => $dateStr . ': Update StatsCouponLogDaily'], 'update_coupon_log');
        }
        return true;
    }
}
