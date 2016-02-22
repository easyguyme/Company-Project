<?php
namespace backend\modules\member\job;

use backend\components\resque\SchedulerJob;
use backend\models\Account;
use backend\modules\member\models\Member;
use backend\models\Message;

/**
* Job for send member expired message
*/
class MessageMemberExpired extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Send member expired message every day"}
     */
    public function perform()
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
                            if ($expiredCount == 1) {
                                $content = "msg_have $expiredCount msg_had_expired <a href='/member/member?cardState=3'> msg_click_read </a>";
                            } else {
                                $content = "msg_have $expiredCount msg_had_expireds <a href='/member/member?cardState=3'> msg_click_read </a>";
                            }

                        } else if ($day === 1) {
                            if ($expiredCount == 1) {
                                $content = "msg_have $expiredCount msg_having_expired <a href='/member/member?cardState=1'> msg_click_read </a>";
                            } else {
                                $content = "msg_have $expiredCount msg_had_expireds <a href='/member/member?cardState=1'> msg_click_read </a>";
                            }

                        } else {
                            $date = date("Y-m-d", $startDayTimestamp / 1000);
                            if ($expiredCount == 1) {
                                $content = "msg_have $expiredCount msg_having $date msg_expired <a href='/member/member?cardState=2'> msg_click_read </a>";
                            } else {
                                $content = "msg_have $expiredCount msg_havings $date msg_expired <a href='/member/member?cardState=2'> msg_click_read </a>";
                            }

                        }
                        $this->recordMessage($accountId, $content);
                    }
                }
            }
        }
    }

    private function recordMessage($accountId, $content)
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
}
