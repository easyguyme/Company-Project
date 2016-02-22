<?php
namespace backend\modules\member\job;

use backend\models\Account;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberLogs;
use backend\modules\member\models\StatsMemberGrowthMonthly as ModelStatsMemberGrowthMonthly;
use backend\modules\resque\components\ResqueUtil;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;

class StatsMemberGrowthMonthly extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Stats of StatsMemberGrowthMonthly every day"}
     * @author Rex Chen
     */
    public function perform()
    {
        $args = $this->args;
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m', $datetime);
        $startTime = new \MongoDate(strtotime($dateStr . '-01'));
        $endTime = new \MongoDate(strtotime($dateStr . '-01 +1 month'));

        $accounts = Account::findAll(['enabledMods' => 'member']);
        foreach ($accounts as $account) {
            $accountId = $account->_id;
            $totalMember = Member::countByAccount($accountId, null, $endTime);
            $totalActive = MemberLogs::getTotalActiveByAccount($accountId, $startTime, $endTime);
            $totalNew = Member::countByAccount($accountId, $startTime, $endTime);
            $statsGrowth = ModelStatsMemberGrowthMonthly::getByMonthAndAccount($accountId, $dateStr);
            if (empty($statsGrowth)) {
                $statsGrowth = new ModelStatsMemberGrowthMonthly;
                $statsGrowth->accountId = $accountId;
                $statsGrowth->month = $dateStr;
            }
            $statsGrowth->totalNew = $totalNew;
            $statsGrowth->totalActive = $totalActive;
            $statsGrowth->totalInactive = $totalMember - $totalActive;
            try {
                $statsGrowth->save();
            } catch (Exception $e) {
                ResqueUtil::log(['Update StatsMemberGrowthMonthly error' => $e->getMessage(), 'StatsMemberGrowthMonthly' => $statsGrowth]);
                continue;
            }
        }

        return true;
    }
}
