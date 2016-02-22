<?php
namespace backend\modules\member\job;

use MongoId;
use backend\modules\member\models\ScoreRule;
use backend\modules\member\models\Member;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\member\models\ScoreHistory;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;
use backend\models\Account;
use backend\modules\product\models\Coupon;
use backend\utils\LogUtil;
use backend\modules\product\models\MembershipDiscount;
use backend\modules\product\models\CouponLog;

/**
* Job for birthday
*/
class Birthday extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Issue birthday score every day"}
     */
    public function perform()
    {
        $args = $this->args;
        //Delay issue birthday score every day
        if (empty($args['memberId']) || empty($args['accountId'])) {
            $accounts = Account::findAll(['enabledMods' => 'member']);
            foreach ($accounts as $account) {
                $this->getBirthDayReward($account->_id);
            }
        } else {
            //issue birthday score when update member profile
            $this->getBirthDayReward(new MongoId($args['accountId']), new MongoId($args['memberId']));
        }
        return true;
    }

    private function getBirthDayReward($accountId, $memberId = null)
    {
        //get reward type
        $birthdayRule = ScoreRule::getByName(ScoreRule::NAME_BIRTHDAY, $accountId);
        if (!empty($birthdayRule) && $birthdayRule->isEnabled && $birthdayRule->rewardType == ScoreRule::REWARD_SCORE_TYPE) {
            //score
            $this->issueBirthdayScore($accountId, $birthdayRule, $memberId);
        } else if (!empty($birthdayRule) && $birthdayRule->isEnabled && $birthdayRule->rewardType == ScoreRule::REWARD_COUPON_TYPE) {
            //coupon
            $this->issueBirthdayCoupon($accountId, $birthdayRule, $memberId);
        }
    }

    private function issueBirthdayCoupon($accountId, $birthdayCoupon, $memberId)
    {
        //check coupon
        $couponId = $birthdayCoupon->couponId;

        $suitMemberIds = $this->getRewardMemberIds($accountId, $memberId, $birthdayCoupon);

        if (!empty($suitMemberIds)) {
            $members = Member::findAll(['_id' => ['$in' => $suitMemberIds]]);
            $coupon = Coupon::findByPk($couponId);
            if (empty($coupon)) {
                LogUtil::error(['message' => 'can not find the coupon', 'couponId' => (string)$couponId], 'member');
                return false;
            }
            $total = count($members);
            $result = Coupon::updateAll(['$inc' => ['total' => (0 - $total)]], ['total' => ['$gte' => $total], '_id' => $couponId]);
            if ($result) {
                foreach ($members as $member) {
                    //issue membershipdiscount
                    $membershipDiscount = MembershipDiscount::transformMembershipDiscount($coupon, $member, ScoreRule::NAME_BIRTHDAY);

                    if (false === $membershipDiscount->save()) {
                        Coupon::updateAll(['$inc' => ['total' => 1]], ['_id' => $couponId]);
                        LogUtil::error(['message' => 'add membershipdiscount fail', 'memberId' => (string)$member->_id, 'couponId' => (string)$coupon->_id], 'member');
                    } else {
                        LogUtil::info(['message' => 'issue coupon successful', 'couponId' => (string)$couponId, 'memberId' => (string)$member->_id], 'member');
                    }
                }
            } else {
                LogUtil::error(['message' => 'coupon is not enough when give member birthday reward'], 'member');
                return false;
            }
        }
    }

    private function getRewardMemberIds($accountId, $memberId, $birthdayRule)
    {
        if (!empty($memberId)) {
            $memberIds = $this->getMembers($accountId, $birthdayRule->triggerTime, $memberId);
        } else {
            $memberIds = $this->getMembers($accountId, $birthdayRule->triggerTime);
        }

        $thisYear = TimeUtil::thisYear();

        if ($birthdayRule->rewardType == ScoreRule::REWARD_SCORE_TYPE) {
            $scoreHistoryMemberIds = ScoreHistory::getAllMemberIdByRuleName(ScoreRule::NAME_BIRTHDAY, $memberIds, $accountId, $thisYear);
            $memberIds = array_diff($memberIds, $scoreHistoryMemberIds);
            //check this rule whether reward in coupon
            $couponLogMemberIds = CouponLog::getAllMemberIdByRuleName(ScoreRule::NAME_BIRTHDAY, $memberIds, $accountId);
            $memberIds = array_diff($memberIds, $couponLogMemberIds);
        } else if ($birthdayRule->rewardType == ScoreRule::REWARD_COUPON_TYPE) {
            $couponLogMemberIds = CouponLog::getAllMemberIdByRuleName(ScoreRule::NAME_BIRTHDAY, $memberIds, $accountId, $thisYear);
            $memberIds = array_diff($memberIds, $couponLogMemberIds);
            //check this rule whethe reward in score
            $scoreHistoryMemberIds = ScoreHistory::getAllMemberIdByRuleName(ScoreRule::NAME_BIRTHDAY, $memberIds, $accountId, $thisYear);
            $memberIds = array_diff($memberIds, $scoreHistoryMemberIds);
        }
        return $memberIds;
    }

    private function issueBirthdayScore($accountId, $birthdayScore, $memberId = null)
    {
        if (!empty($birthdayScore) && $birthdayScore->isEnabled && $birthdayScore->rewardType == ScoreRule::REWARD_SCORE_TYPE) {
            //reward score
            $suitMemberIds = $this->getRewardMemberIds($accountId, $memberId, $birthdayScore);
            $memberList = Member::giveScoreByIds($birthdayScore->score, $suitMemberIds);

            //update history
            foreach ($memberList as $id) {
                $scoreHistory = new ScoreHistory();
                $scoreHistory->assigner = ScoreHistory::ASSIGNER_RULE;
                $scoreHistory->increment = $birthdayScore->score + 0;
                $scoreHistory->memberId = $id;
                $scoreHistory->brief = ScoreHistory::ASSIGNER_RULE;
                $scoreHistory->description = $birthdayScore->name;
                $scoreHistory->channel = ['origin' => ScoreHistory::PORTAL];
                $scoreHistory->accountId = $accountId;

                if (!$scoreHistory->save()) {
                    LogUtil::error(['message' => 'birthday score member', 'member' => $memberList], 'member');
                }
            }
        }
    }

    private function getTimeCondition($triggerTime)
    {
        //get month and day today is 2015-1-20, $todayMD = 120
        $todayMD = date('n', time()) * 100 + date('j', time());

        switch ($triggerTime) {
            case ScoreRule::TRIGGER_TIME_DAY:
                $timeFrom = $timeTo = $todayMD;
                break;
            case ScoreRule::TRIGGER_TIME_WEEK:
                //Numeric representation of the day of the week, 0-6
                $numeric = date('w');
                //days to weekend
                $addDay = 7 - $numeric;
                //days to last weekend
                $subDay = 1 - $numeric;
                $timeFrom = $this->_getWeekTime($subDay);
                $timeTo = $this->_getWeekTime($addDay);
                break;
            case ScoreRule::TRIGGER_TIME_MONTH:
                $month = (int) ($todayMD / 100);
                $timeFrom = $month * 100 + 1;
                $timeTo = $month * 100 + 31;
                break;
            default:
                $timeFrom = $timeTo = 0;
                break;
        }

        return ['timeFrom' => $timeFrom, 'timeTo' => $timeTo];
    }

    /**
     * Get member by birth according score rule trigger time
     * @param string $triggerTime
     * @return member list
     */
    private function getMembers($accountId, $triggerTime, $memberId = null)
    {
        $timeCondition = $this->getTimeCondition($triggerTime);

        $memberIds = [];
        if (!empty($timeCondition['timeFrom']) && !empty($timeCondition['timeTo'])) {
            if (empty($memberId)) {
                $members = Member::searchByBirth($timeCondition['timeFrom'], $timeCondition['timeTo'], $accountId);
                $memberIds = Member::getIdList($members);
            } else {
                $condition = ['birth' => ['$gte' => $timeCondition['timeFrom'], '$lte' => $timeCondition['timeTo']]];
                $member = Member::findByPk($memberId, $condition);
                if (!empty($member)) {
                    $memberIds = [$member->_id];
                }
            }
        }

        return $memberIds;
    }

    private function _getWeekTime($addDay)
    {
        $todayMD = date('n', time()) * 100 + date('j', time());
        $daysCount = date('t', time());

        $month = (int) ($todayMD / 100);
        $day = $todayMD % 100;

        if ($addDay > 0) {
            if (($day + $addDay) > $daysCount) {
                //to next month, for example: if today is Tuesday 03-30, time to 04-04
                //$day = 30, $addDay = 5, $daysCount = 31, $result = 404
                $result = ($month + 1) * 100 + $day + $addDay - $daysCount;
            } else {
                $result = $todayMD + $addDay;
            }

            if ($month == 12) {
                $result = 1231;
            }
        } else {
            if (($day + $addDay) < 1) {
                //to next month, for example: if today is Tuesday 04-1, time From 03-31
                //$day = 1, $addDay = -1, $daysCount = 31, $result = 331
                $result = ($month - 1) * 100 + $day + $addDay + $daysCount;
            } else {
                $result = $todayMD + $addDay;
            }

            if ($month == 1) {
                $result = 101;
            }
        }

        return $result;
    }
}
