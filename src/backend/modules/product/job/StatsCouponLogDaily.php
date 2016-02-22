<?php
namespace backend\modules\product\job;

use MongoDate;
use backend\components\resque\SchedulerJob;
use backend\utils\TimeUtil;
use backend\modules\product\models\CouponLog;
use backend\modules\product\models\StatsCouponLogDaily as ModelStatsCouponLogDaily;
use backend\utils\LogUtil;

class StatsCouponLogDaily extends SchedulerJob
{
    /**
     * @args {"description": "Direct: Stats of coupon"}
     */
    public function perform()
    {
        $args = $this->args;
        //Get date from args or today
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m-d', $datetime);

        $stats = CouponLog::getStats($dateStr);

        if (!empty($stats)) {
            self::createStatsCouponLog($dateStr, $stats);
        }
        return true;
    }

    public static function createStatsCouponLog($dateStr, $stats)
    {
        //check data whether exists today
        $result = ModelStatsCouponLogDaily::findOne(['date' => $dateStr]);
        if ($result) {
            LogUtil::info(['message' => $dateStr . ' coupon log data is exists'], 'resque');
            return true;
        }
        $stats = CouponLog::formatStruct($stats);
        $data = $couponIds = [];
        foreach ($stats as $stat) {
            $receivedKey = $stat['couponId'] . '_' . CouponLog::RECIEVED;
            $redeemedKey = $stat['couponId'] . '_' . CouponLog::REDEEMED;

            if (!in_array($stat['couponId'], $couponIds)) {
                $couponIds[] = $stat['couponId'];

                $receivedNum = isset($stats[$receivedKey]['count']) ? $stats[$receivedKey]['count'] : 0;
                $redeemedNum = isset($stats[$redeemedKey]['count']) ? $stats[$redeemedKey]['count'] : 0;
                $data[] = [
                    'couponId' => $stat['couponId'],
                    'accountId' => $stat['accountId'],
                    'recievedNum' => $receivedNum + $redeemedNum,
                    'redeemedNum' => $redeemedNum,
                    'date' =>  $dateStr,
                ];
            }
        }
        unset($stat, $stats, $couponIds);
        if (false === ModelStatsCouponLogDaily::batchInsert($data)) {
            LogUtil::error(['message' => 'Faild to create statis daily couponlog', 'date' => $dateStr, 'data' => json_encode($data)], 'resque');
        }
    }
}
