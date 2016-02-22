<?php
namespace backend\modules\member\job;

use backend\models\StatsMemberDaily as ModelStatsMemberDaily;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\member\models\StatsMemberMonthly as ModelStatsMemberMonthly;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;

/**
* Job for StatsMemberDaily
*/
class StatsMemberMonthly extends SchedulerJob
{
    public function perform()
    {
        $args = $this->args;

        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m', $datetime);

        $monthData = ModelStatsMemberDaily::getMonthData($dateStr);

        $rowsMonthly = [];
        foreach ($monthData as $item) {
            $origin = $item['_id']['origin'];
            $originName = $item['_id']['originName'];
            $accountId = $item['_id']['accountId'];
            $total = $item['total'];
            $statsMemberMonthly = ModelStatsMemberMonthly::getByDateAndOriginInfo($dateStr, $origin, $originName, $accountId);
            if (!empty($statsMemberMonthly)) {
                $statsMemberMonthly->total = $total;
                try {
                    $statsMemberMonthly->save(true, ['total']);
                } catch (Exception $e) {
                    ResqueUtil::log(['Update StatsMemberMonthly error' => $e->getMessage(), 'StatsMemberMonthly' => $statsMemberMonthly]);
                    continue;
                }
            } else {
                $rowsMonthly[] = [
                    'month' => $dateStr,
                    'origin' => $origin,
                    'originName' => $originName,
                    'accountId' => $accountId,
                    'total' => $total,
                ];
            }
        }
        ModelStatsMemberMonthly::batchInsert($rowsMonthly);

        return true;
    }
}
