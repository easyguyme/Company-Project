<?php
namespace backend\modules\product\job;

use MongoDate;
use backend\modules\product\models\MembershipDiscount;
use backend\components\resque\SchedulerJob;

class DisableExpiredCoupon extends SchedulerJob
{
    public function perform()
    {
        $now = new MongoDate(strtotime(date('Y-m-d')));
        $result = MembershipDiscount::updateAll(
            ['$set' => ['coupon.status' => MembershipDiscount::EXPIRED]],
            [
                'coupon.endTime' => ['$lt' => $now],
                'coupon.status' => ['$in' => [MembershipDiscount::UNUSED, MembershipDiscount::USED]]
            ]
        );
        echo $result;
    }
}
