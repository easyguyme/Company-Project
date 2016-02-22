<?php
namespace console\modules\cron\controllers;

use yii\console\Controller;
use backend\components\BaseModel;
use backend\modules\member\models\MemberShipCard;
use backend\models\Message;
use backend\modules\member\models\Member;
use backend\models\Account;
use backend\modules\product\models\PromotionCodeAnalysis;
use Yii;

/**
 * Those commands which is perform once a day.
 */
class DailyController extends Controller
{
    const ACCOUND_ID = KLP_ACCOUNT_ID;

    private $properties = ['operate', 'subChannel'];

    /**
    * Execute all commands that is once a day.
    */
    public function actionIndex()
    {
           $this->actionStatisticsUhkklp();
//         $this->actionDailyJob();
//         $this->actionQuestionnaireStats();
//         $this->actionCheckCardExpiredTime();
//         $this->actionStatsCouponLogDaily();
//         $this->actionPromotionCodeAnalysis();
    }

    /**
    * Automatic stats coipon data.
    */
    public function actionStatsCouponLogDaily()
    {
        $dailyArgs = ['description' => 'Direct: Stats of coupon'];
        Yii::$app->job->create('backend\modules\product\job\StatsCouponLogDaily', $dailyArgs);
    }

    /**
    * Automatic clear member score.
    */
    public function actionResetScore()
    {
        $month = intval(date('m'));
        $day = intval(date('d'));
        $memberShipCards = MemberShipCard::find()->where(['isDeleted' => BaseModel::NOT_DELETED, 'scoreResetDate.month' => $month, 'scoreResetDate.day' => $day])->all();
        if (!empty($memberShipCards)) {
            foreach ($memberShipCards as $memberShipCard) {
                $members = Member::find()->where(['isDeleted' => BaseModel::NOT_DELETED, 'cardId' => $memberShipCard->_id, 'score' => ['$ne' => 0]])->all();

                if (!empty($members)) {
                    foreach ($members as $member) {
                        $member->resetScore();
                    }
                }
                Member::updateAll(['$set' => ['totalScoreAfterZeroed' => 0]], ['cardId' => $memberShipCard->_id]);
            }
        }
    }

    /**
    *  Check expired membership card.
    */
    public function actionCheckCardExpiredTime()
    {
        $accounts = Account::find()->where(['isDeleted' => Account::NOT_DELETED])->all();

        if (!empty($accounts)) {
            $nowTimestamp = strtotime(date('Y-m-d')) * 1000;
            $oneDayTimestamp = 24 * 60 * 60 * 1000;

            foreach ($accounts as $account) {
                $accountId = $account->_id;

                for ($day = 0; $day <= 7; $day++) {
                    $startDayTimestamp = $nowTimestamp + $oneDayTimestamp * ($day - 1);
                    $endDayTimestamp = $nowTimestamp + $oneDayTimestamp * $day;
                    if ($day === 0) {
                        $startDayTimestamp = 0;
                    }
                    $expiredCount = Member::find()->where(['accountId' => $accountId, 'cardExpiredAt' => ['$lte' => $endDayTimestamp, '$gt' => $startDayTimestamp], 'isDeleted' => Member::NOT_DELETED])->count();
                    if ($expiredCount > 0) {
                        if ($day === 0) {
                            $content = "有 $expiredCount 个会员的会员卡已过期, <a href='/member/member?cardState=3'>(点击查看)</a>";
                        } else if ($day === 1) {
                            $content = "有 $expiredCount 个会员的会员卡即将于1天内过期, <a href='/member/member?cardState=1'>(点击查看)</a>";
                        } else {
                            $date = date("Y-m-d", $startDayTimestamp / 1000);
                            $content = "有 $expiredCount 个会员的会员卡即将于 $date 过期, <a href='/member/member?cardState=2'>(点击查看)</a>";
                        }
                        $this->_recordMessage($accountId, $content);
                    }
                }
            }
        }
    }

    /**
     * Stats of question naire answer
     */
    public function actionQuestionnaireStats()
    {
        $dailyArgs = ['description' => 'Direct: Stats of questionnaire answer'];
        Yii::$app->job->create('backend\modules\content\job\StatsQuestionnaireAnswerDaily', $dailyArgs);

        $dailyArgs['description'] = 'Direct: Stats of questionnaire';
        Yii::$app->job->create('backend\modules\content\job\StatsQuestionnaireDaily', $dailyArgs);
    }

    /**
    * Create data for promotioncode analysis.
    */
    public function actionPromotionCodeAnalysis()
    {
        $this->_daily();
        $this->_participant();
        $this->_total();
        $this->_totalparticipant();
    }

    /**
    * Stats of uhkklp.
    * Create stats jobs. Should be performed once a day. Managed with cron job.
    */
    public function actionStatisticsUhkklp()
    {
        $accountIds = Account::getActivatedAccountIdList();
        if (!empty($accountIds)) {
            $dailyArgs = [
                'description' => 'Direct: Stats of StatsMemberDaily',
                'runNextJob' => true,
                'accountId' => $accountIds,
                'properties' => $this->properties,
            ];

            $dailyArgs['description'] = 'Direct: Stats of StatsMemberCampaignLogDaily';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberCampaignLogDaily', $dailyArgs);

            $dailyArgs['description'] = 'Direct: Stats of StatsMemberPropertyMonthly';
            Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropertyMonthly', $dailyArgs);
        }
    }

    /**
    * update StatsMemberCampaignLogDaily.(update-stats)
    */
    public function actionUpdateStats($startDate, $endDate, $accountId)
    {
        if ($accountId == 'all') {
            $accountIds = Account::getActivatedAccountIdList();
            if (empty($accountIds)) {
                echo 'Account id is empty' . PHP_EOL;
                exit();
            }
            $accountId = $accountIds;
        } else {
            $accountId = [$accountId];
        }
        $dailyArgs = [
            'description' => 'Direct: update Stats of MemberCampaignLogDaily',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'accountId' => $accountId,
            'properties' => $this->properties,
        ];

        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberCampaignLogDailyUpdate', $dailyArgs, null, null, false);
        echo 'Job is runing, jobId:' . $jobId . PHP_EOL;
    }

    /**
    * update StatsMemberCampaignLogDaily.(update-member-property-monthly)
    */
    public function actionUpdateMemberPropertyMonthly($startDate, $endDate, $accountId)
    {
        if ($accountId == 'all') {
            $accountIds = Account::getActivatedAccountIdList();
            if (empty($accountIds)) {
                echo 'Account id is empty' . PHP_EOL;
                exit();
            }
            $accountId = $accountIds;
        } else {
            $accountId = [$accountId];
        }
        $dailyArgs = [
            'description' => 'Direct: update Stats of MemberPropertyMonthly',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'accountId' => $accountId,
            'properties' => $this->properties,
        ];

        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\StatsMemberPropertyMonthlyUpdate', $dailyArgs, null, null, false);
        echo 'Job is runing, jobId:' . $jobId . PHP_EOL;
    }

    /**
    * Member module daily job.
    * Create stats jobs. Should be performed once a day. Managed with cron job.
    * 1. Issue birthday score
    * 2. Stats member location and score-rule issued times
    */
    public function actionDailyJob()
    {
        $statisticsArgs = ['description' => 'Direct: Statistics of location info and scores issued by rule'];
        Yii::$app->job->create('backend\modules\member\job\Statistics', $statisticsArgs);

        $birthdayArgs = ['description' => 'Direct: Issue birthday score daily'];
        Yii::$app->job->create('backend\modules\member\job\Birthday', $birthdayArgs);
    }

    private function _recordMessage($accountId, $content)
    {
        $message = new Message;
        $message->title = 'member_card_expiration_reminder';
        $message->accountId = $accountId;
        $message->status = Message::STATUS_WARNING;
        $message->to = ['id' => Message::ID_ACCOUNT, 'target' => Message::TO_TARGET_ACCOUNT];
        $message->sender = ['id' => Message::ID_SYSTEM, 'from' => Message::SENDER_FROM_SYSTEM];
        $message->content = $content;
        $message->save();
    }

    /**
     * Every day to sum the code to be redeemed.
     */
    private function _daily()
    {
        $args['description'] = 'Direct: Analysis daily promotion code';
        $jobId = Yii::$app->job->create('backend\modules\product\job\DailyAnalysis', $args);
        echo $jobId . PHP_EOL;
    }

    /**
     * Every day to sum how manuy perple to take part in the campaign.
     */
    private function _participant()
    {
        $args['description'] = 'Direct: Analysis participate promotion code';
        $jobId = Yii::$app->job->create('backend\modules\product\job\ParticipateAnalysis', $args);
        echo $jobId . PHP_EOL;
    }

     /**
     * Every day to sum how manuy perple to take part in the campaign.
     */
    private function _totalparticipant()
    {
        $args['description'] = 'Direct: Analysis participate promotion code';
        $jobId = Yii::$app->job->create('backend\modules\product\job\TotalParticipateAnalysis', $args);
        echo $jobId . PHP_EOL;
    }

    /**
     * Every day to sum how many codes to be redeemed still now.
     */
    private function _total()
    {
        $args['description'] = 'Direct: Analysis total promotion code';
        $jobId = Yii::$app->job->create('backend\modules\product\job\TotalAnalysis', $args);
        echo $jobId . PHP_EOL;
    }
}
