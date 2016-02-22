<?php
namespace backend\components\extservice\models;

use MongoId;
use MongoDate;
use backend\modules\product\models\Coupon as ModelCoupon;
use backend\modules\product\models\MembershipDiscount as ModelMembershipDiscount;
use backend\utils\MongodbUtil;
use backend\utils\LogUtil;

/**
 * Coupon for extension
 * @author Mike Wang
 */
class Coupon extends BaseComponent
{
    public function getByMemberIdAndCouponId($memberId, $couponId)
    {
        return ModelCoupon::findByPk($couponId, ['type' => ModelCoupon::COUPON_CASH]);
    }

    public function getByCode($couponCode)
    {
        $condition = [
            'coupon.status' => ModelMembershipDiscount::UNUSED,
            'code' => $couponCode,
            'accountId' => $this->accountId
        ];
        $membershipDiscount = ModelMembershipDiscount::findOne($condition);
        if (!empty($membershipDiscount)) {
            return $membershipDiscount->coupon;
        }
    }

    public function isAvaliable($memberId, $couponCode)
    {
        $condition = [
            'member.id' => $memberId,
            'coupon.status' => ModelMembershipDiscount::UNUSED,
            'code' => $couponCode
        ];
        //if the coupon is expired,no need to show
        $current = new MongoDate(strtotime(date('Y-m-d')));
        $condition['coupon.endTime'] = ['$gte' => $current];
        $condition['coupon.startTime'] = ['$lte' => $current];
        $membershipDiscount = ModelMembershipDiscount::findOne($condition);
        if (!empty($membershipDiscount)) {
            return true;
        }
        return false;
    }

    public function makeUsed($memberId, $couponCode)
    {
        if (is_string($memberId)) {
            $memberId = new MongoId($memberId);
        }
        $condition = [
            'member.id' => $memberId,
            'code' => $couponCode
        ];
        $update = [
            'coupon.status' => ModelMembershipDiscount::USED
        ];

        $discount = ModelMembershipDiscount::find()->where($condition)->orderBy(['createdAt' => SORT_ASC])->one();
        if (!empty($discount)) {
            LogUtil::info(['membershipDiscount' => $discount->toArray()], 'channel-webhook');
            $coupon = $discount['coupon'];
            $coupon['status'] = ModelMembershipDiscount::USED;
            $discount->coupon = $coupon;
            LogUtil::info(['membershipDiscount' => $discount->toArray()], 'channel-webhook');
            return $discount->save(true, ['coupon']);
        }
    }
}
