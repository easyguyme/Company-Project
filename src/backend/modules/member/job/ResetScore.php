<?php
namespace backend\modules\member\job;

use backend\components\resque\SchedulerJob;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\Member;

/**
* Job for automatic clear member score.
*/
class ResetScore extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Automatic clear member score. every day"}
     */
    public function perform()
    {
        $month = intval(date('m'));
        $day = intval(date('d'));
        $memberShipCards = MemberShipCard::find()->where(['isDeleted' => MemberShipCard::NOT_DELETED, 'scoreResetDate.month' => $month, 'scoreResetDate.day' => $day])->all();
        if (!empty($memberShipCards)) {
            foreach ($memberShipCards as $memberShipCard) {
                $members = Member::find()->where(['isDeleted' => MemberShipCard::NOT_DELETED, 'cardId' => $memberShipCard->_id, 'score' => ['$ne' => 0]])->all();

                if (!empty($members)) {
                    foreach ($members as $member) {
                        $member->resetScore();
                    }
                }
                Member::updateAll(['$set' => ['totalScoreAfterZeroed' => 0]], ['cardId' => $memberShipCard->_id]);
            }
        }
    }
}
