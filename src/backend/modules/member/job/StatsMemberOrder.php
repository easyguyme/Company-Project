<?php
namespace backend\modules\member\job;

use Yii;
use MongoDate;
use backend\components\resque\SchedulerJob;
use backend\models\Account;
use backend\modules\member\models\StatsOrder;
use backend\utils\MongodbUtil;
use backend\modules\member\models\StatsMemberOrder as ModelStatsMemberOrder;
use backend\models\Order;
use backend\utils\LogUtil;

/**
* Job for StatsMemberOrder
*/
class StatsMemberOrder extends SchedulerJob
{
    /**
     * @args {"description": "Delay: stats of member orders", "runNextJob": true}
     * @author Rex Chen
     */
    public function perform()
    {
        $dateStr = date('Y-m-d');
        $operateTimeTo = new MongoDate(strtotime($dateStr));
        $accounts = Account::findAll([]);
        foreach ($accounts as $account) {
            $modelStatsOrder = StatsOrder::getLatestByAccount($account->_id);
            if (empty($modelStatsOrder)) {
                $operateTimeFrom = null;
            } else {
                //ensure Y-m-d mongodate
                $operateDate = MongodbUtil::MongoDate2String($modelStatsOrder->createdAt, 'Y-m-d');
                if ($dateStr === $operateDate) {
                    return true;
                }
                $operateTimeFrom = new MongoDate(strtotime($operateDate));
            }
            $this->statsMemberOrder($account->_id, $operateTimeFrom, $operateTimeTo);
            $this->updateMemberRecentTransactionCount($account->_id);
        }
    }

    /**
     * Stats member order
     * @param MongoId $accountId
     * @param MongoDate $operateTimeFrom
     * @param MongoDate $operateTimeTo
     */
    private function statsMemberOrder($accountId, $operateTimeFrom, $operateTimeTo)
    {
        $memberStats = Order::getMemberStats($accountId, $operateTimeFrom, $operateTimeTo);
        $memberStatsRows = [];
        foreach ($memberStats as $stat) {
            $consumerId = $stat['consumer.id'];
            $memberStats = ModelStatsMemberOrder::getByConsumerId($accountId, $consumerId);
            if (empty($memberStats)) {
                $memberStatsRows[] = [
                    'consumerId' => $consumerId,
                    'consumptionAmount' => $stat['consumptionAmount'],
                    'transactionCount' => $stat['transactionCount'],
                    'maxConsumption' => $stat['maxConsumption'],
                    'accountId' => $accountId
                ];
            } else {
                $memberStats->consumptionAmount += $stat['consumptionAmount'];
                $memberStats->transactionCount += $stat['transactionCount'];
                $memberStats->maxConsumption = $memberStats->maxConsumption > $stat['maxConsumption'] ? $memberStats->maxConsumption : $stat['maxConsumption'];
                if (!$memberStats->save(true, ['consumptionAmount', 'transactionCount', 'maxConsumption'])) {
                    LogUtil::error(['message' => 'Stats member order', 'date' => $dateStr, 'memberStats' => $memberStats], 'resque');
                }
            }
        }
        $batchInsertResult = ModelStatsMemberOrder::batchInsert($memberStatsRows);
        if (!$batchInsertResult) {
            LogUtil::error(['message' => 'Stats member order', 'date' => $dateStr, 'memberStats' => $memberStatsRows], 'resque');
        }
    }

    /**
     * Update recent transaction count
     * @param MongoId $accountId
     */
    private function updateMemberRecentTransactionCount($accountId)
    {
        $operatTimeFrom = new MongoDate(strtotime(date('Y-m-d') . ' -6 months'));
        $operatTimeTo = new MongoDate(strtotime(date('Y-m-d')));
        $recentTransactionStats = Order::getMemberTransactionCount($accountId, $operatTimeFrom, $operatTimeTo);
        foreach ($recentTransactionStats as $recentTransactionStat) {
            ModelStatsMemberOrder::updateAll(['$set' => ['recentTransactionCount' => $recentTransactionStat['count']]], ['consumerId' => $recentTransactionStat['_id']]);
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        $args = $this->args;
        if (!empty($args['runNextJob'])) {
            $args = [];
            $args['description'] = 'Delay: Stats of account orders';
            Yii::$app->job->create('backend\modules\member\job\StatsOrder', $args);
        }
    }
}
