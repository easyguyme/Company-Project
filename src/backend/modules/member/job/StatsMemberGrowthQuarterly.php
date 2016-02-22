<?php
namespace backend\modules\member\job;

use backend\models\Account;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberLogs;
use backend\modules\member\models\StatsMemberGrowthQuarterly as ModelStatsMemberGrowthQuarterly;
use backend\modules\resque\components\ResqueUtil;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;

class StatsMemberGrowthQuarterly extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Stats of StatsMemberGrowthQuarterly every day"}
     * @author Rex Chen
     */
    public function perform()
    {
        $args = $this->args;
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $year = date('Y', $datetime);
        $quarter = TimeUtil::getQuarter($datetime);

        $startMonth = ($quarter - 1) * 3 + 1;
        $startTime = new \MongoDate(strtotime($year . '-' . $startMonth . '-01'));
        $endTime = new \MongoDate(strtotime($year . '-' . $startMonth . '-01' . ' +3 month'));

        $accounts = Account::findAll(['enabledMods' => 'member']);
        foreach ($accounts as $account) {
            $accountId = $account->_id;
            $totalMember = Member::countByAccount($accountId, null, $endTime);
            $totalActive = MemberLogs::getTotalActiveByAccount($accountId, $startTime, $endTime);
            $totalNew = Member::countByAccount($accountId, $startTime, $endTime);
            $statsGrowth = ModelStatsMemberGrowthQuarterly::getByQuarterAndAccount($accountId, $year, $quarter);
            if (empty($statsGrowth)) {
                $statsGrowth = new ModelStatsMemberGrowthQuarterly;
                $statsGrowth->accountId = $accountId;
                $statsGrowth->year = $year;
                $statsGrowth->quarter = $quarter;
            }
            $statsGrowth->totalNew = $totalNew;
            $statsGrowth->totalActive = $totalActive;
            $statsGrowth->totalInactive = $totalMember - $totalActive;
            try {
                $statsGrowth->save();
            } catch (Exception $e) {
                ResqueUtil::log(['Update StatsMemberGrowthQuarterly error' => $e->getMessage(), 'StatsMemberGrowthMonthly' => $statsGrowth]);
                continue;
            }
        }

        return true;
    }
}
