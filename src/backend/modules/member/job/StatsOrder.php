<?php
namespace backend\modules\member\job;

use Yii;
use backend\modules\member\models\StatsOrder as ModelStatsOrder;
use backend\modules\member\models\StatsMemberOrder;
use backend\utils\LogUtil;
use backend\components\resque\SchedulerJob;
use backend\models\Account;

/**
* Job for StatsOrder
*/
class StatsOrder extends SchedulerJob
{
    /**
     * @args {"description": "Delay: stats of account orders"}
     * @author Rex Chen
     */
    public function perform()
    {
        $stats = StatsMemberOrder::getStatsByAccount();
        if (!ModelStatsOrder::batchInsert($stats)) {
            LogUtil::error(['message' => 'Stats account order', 'date' => date('Y-m-d'), 'statsOrder' => $stats], 'resque');
        }
    }
}
