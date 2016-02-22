<?php
namespace backend\behaviors;

use MongoDate;
use yii\base\Behavior;
use backend\modules\product\models\CouponLog;
use backend\modules\member\models\Member;
use backend\utils\LogUtil;

class MembershipDiscountBehavior extends Behavior
{
    public function operationCoupon($membershipDiscount)
    {
        //record log when get coupon
        $memberItem = Member::getMemberInfo($membershipDiscount->member['id'], ['tel']);

        $memberInfo = [
            'id' => $membershipDiscount->member['id'],
            'name' => $membershipDiscount->member['name'],
            'phone' => empty($memberItem['tel']) ? '' : $memberItem['tel'],
            'receiveType' => $membershipDiscount->coupon['receiveType'],
        ];
        $couponLog = new CouponLog();
        $couponLog->couponId = $membershipDiscount->coupon['id'];
        $couponLog->membershipDiscountId = $membershipDiscount->_id;
        $couponLog->type = $membershipDiscount->coupon['type'];
        $couponLog->title = $membershipDiscount->coupon['title'];
        $couponLog->status = CouponLog::RECIEVED;
        $couponLog->member = $memberInfo;
        $couponLog->total = 1;
        $couponLog->operationTime = $couponLog->createdAt = new MongoDate();
        $couponLog->accountId = $membershipDiscount->accountId;

        if (false === $couponLog->save()) {
            //to avoid the error show to user
            LogUtil::error(['message' => 'Failed to save couponLog error:' . $couponLog->getErrors()], 'product');
        }
    }
}
