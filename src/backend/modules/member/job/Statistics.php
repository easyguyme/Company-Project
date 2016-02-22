<?php
namespace backend\modules\member\job;

use backend\modules\member\models\ScoreRule;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberStatistics;
use backend\utils\LogUtil;
use backend\components\resque\SchedulerJob;
use backend\models\Account;
use backend\modules\product\models\CouponLog;

/**
* Job for member stats of rule score and location stats
*/
class Statistics extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Statistics of location info and scores issued by rule every day"}
     */
    public function perform()
    {
        $args = $this->args;
        $accounts = Account::findAll(['enabledMods' => 'member']);
        foreach ($accounts as $account) {
            $accountId = $account->_id;
            //score rule statistics
            $scoreRules = ScoreRule::getByAccount($accountId);

            foreach ($scoreRules as $scoreRule) {
                $this->updateScoreRuleStatistics($scoreRule, $accountId);
            }

            //location statistics
            $result = $this->updateLocationStatistics($accountId);
            if (!$result) {
                LogUtil::error(['message' => 'statistics location error', 'date' => date('Y-m-d H:i:s'), 'args' => $args], 'resque');
            }
        }

        return true;
    }

    private function updateScoreRuleStatistics($scoreRule, $accountId)
    {
        $ruleName = $scoreRule->name;
        list($scoreTimes, $scoreMemberIds) = $this->getTimeAndmemberCountWithScore($ruleName, $accountId);
        if ($scoreRule->isDefault) {
            $reciveType = $scoreRule->name;
        } else {
            $reciveType = (string) $scoreRule->_id;
        }
        list($couponTimes, $couponMemberIds) = $this->getTimeAndmemberCountWithCoupon($reciveType, $accountId);

        $times = $scoreTimes + $couponTimes;
        $memberCount = count(array_unique(array_merge($scoreMemberIds, $couponMemberIds)));

        $result = ScoreRule::updateAll(
            ['times' => $times, 'memberCount' => $memberCount],
            ['name' => $ruleName, 'accountId' => $accountId]
        );
    }

    private function getTimeAndmemberCountWithScore($ruleName, $accountId)
    {
        $times = ScoreHistory::countByRuleName($ruleName, $accountId);
        $memberIds = ScoreHistory::distinctMemberIdByRuleName($ruleName, $accountId);
        return [$times, $memberIds];
    }

    private function getTimeAndmemberCountWithCoupon($ruleName, $accountId)
    {
        $times = CouponLog::getTotalTimesByRuleName($ruleName, $accountId);
        $memberIds = CouponLog::getAllMemberIdByRuleName($ruleName, [], $accountId, 0);
        return [$times, $memberIds];
    }

    /**
     * Update location statistics
     * @param mongoId $accountId
     * @return boolean
     */
    private function updateLocationStatistics($accountId)
    {
        $memberStatistics = MemberStatistics::getByAccount($accountId);
        if (empty($memberStatistics)) {
            $memberStatistics = new MemberStatistics();
        }
        $locationStatistics = $this->getlocationStatistics($accountId);

        $memberStatistics->locationStatistics = $locationStatistics;
        $memberStatistics->accountId = $accountId;

        return $memberStatistics->save();
    }

    /**
     * Get location statistics info
     * @param mongoId $accountId
     * @param mongoDate $createdAt
     * @return array location statistics info
     */
    private function getlocationStatistics($accountId, $createdAt = null)
    {
        $location = [];
        //get all locations
        $countries = Member::getLocations('country', '', $accountId, $createdAt);

        foreach ($countries as $country) {
            $provinces = Member::getLocations('province', $country, $accountId, $createdAt);
            $provinceTemp = [];
            foreach ($provinces as $province) {
                $cities = Member::getLocations('city', $province, $accountId, $createdAt);
                $cityTemp = [];
                foreach ($cities as $city) {
                    $cityTemp[] = ['value' => $city];
                }
                $temp['cities'] = $cityTemp;
                $temp['value'] = $province;
                $provinceTemp[] = $temp;
            }
            $contyTemp['provinces'] = $provinceTemp;
            $contyTemp['value'] = $country;

            $location[] = $contyTemp;
        }

        return $location;
    }
}
