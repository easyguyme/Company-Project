<?php
namespace backend\modules\product\controllers;

use Yii;
use MongoId;
use MongoDate;
use backend\modules\product\models\CouponLog;
use backend\modules\product\models\Coupon;
use backend\modules\product\models\MembershipDiscount;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;
use backend\modules\member\models\Member;
use backend\utils\UrlUtil;

class MembershipDiscountController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\MembershipDiscount';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();

        $status = $params['status'];
        $id = $params['memberId'];

        if (empty($id)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        return MembershipDiscount::search($id, $status);
    }

    public function actionTotalCash()
    {
        $params = $this->getQuery();
        if (empty($params['memberId']) || empty($params['couponType']) || empty($params['price'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $params['memberId'] = new MongoId($params['memberId']);
        return MembershipDiscount::getTotalCashInfo($params);
    }

    public function actionDelete($id)
    {
        $ids = [];
        $ids[] = new MongoId($id);
        $membershipDiscount = MembershipDiscount::findByPk($ids);

        if (empty($membershipDiscount)) {
            throw new BadRequestHttpException(Yii::t('product', 'membershipDiscount_is_deleted'));
        }
        $isDeMembershipDiscount = MembershipDiscount::deleteAll(['_id' => $ids]);
        if (!$isDeMembershipDiscount) {
            throw new ServerErrorHttpException(Yii::t('content', 'delete_fail'));
        }

        // Update the relate of couponLog collection.
        $field = [
            'status' => MembershipDiscount::DELETED,
            'operationTime' => new \MongoDate()
        ];
        $where = ['membershipDiscountId' => $membershipDiscount->_id];
        CouponLog::updateAll($field, $where);
    }

    /**
     * receive copon through oauth recall this api
     */
    public function actionReceivedCoupon()
    {
        $params = $this->getQuery();
        $defaultId = -1;
        $message = '';

        if (empty($params['couponId']) || empty($params['memberId']) || empty($params['channelId'])) {
            LogUtil::error(['message' => 'missing params when receive coupon', 'params' => $params], 'product');
            exit();
        }

        $number = (!empty($params['number']) && intval($params['number']) > 1) ? intval($params['number']) : 1;

        $couponId = new MongoId($params['couponId']);
        $coupon = Coupon::findByPk($couponId);
        if (empty($coupon)) {
            LogUtil::error(['message' => 'invalid couponIdi when receive coupon', 'params' => $params], 'product');
            exit();
        }

        $memberId = new MongoId($params['memberId']);
        $member = Member::findByPk($memberId);
        if (empty($member)) {
            LogUtil::error(['message' => 'invalid memberId when receive coupon', 'params' => $params], 'product');
            exit();
        }

        $args = [
            'mainDomain' => UrlUtil::getDomain() . '/mobile/product/coupon',
            'couponId' => $params['couponId'],
            'id' => $defaultId,
            'memberId' => $params['memberId'],
            'result' => 'fail',
            'channelId' => $params['channelId'],
        ];
        //check the total
        if ($coupon->total < $number) {
            $message = Yii::t('product', 'coupon_no_exists');
            return $this->_redirectCouponDetail($message, $args, $params);
        }
        //check limit
        $couponNumber = CouponLog::count(['couponId' => $couponId, 'member.id' => $memberId]);
        if ($couponNumber >= $coupon->limit) {
            $message = Yii::t('product', 'coupon_is_received');
            return $this->_redirectCouponDetail($message, $args, $params);
        }
        //check the time
        $current = new MongoDate(strtotime(date('Y-m-d')));
        if ($coupon->time['type'] == Coupon::COUPON_RELATIVE_TIME && $coupon->time['endTime'] < $current) {
            $message = Yii::t('product', 'coupon_expired');
            return $this->_redirectCouponDetail($message, $args, $params);
        }
        //receive coupon
        $where = [
            'total' => ['$gte' => $number],
            '_id' => $couponId
        ];

        $number -= 2 * $number;
        if (Coupon::updateAll(['$inc' => ['total' => $number]], $where)) {
            $membershipDiscount = MembershipDiscount::transformMembershipDiscount($coupon, $member);
            if (false === $membershipDiscount->save()) {
                //to avoid the error show to user
                LogUtil::error(['message' => 'Failed to save couponLog error:' . $membershipDiscount->getErrors()], 'product');
                $message = Yii::t('common', 'save_fail');
                return $this->_redirectCouponDetail($message, $args, $params);
            }

            $args['id'] = isset($membershipDiscount->_id) ? $membershipDiscount->_id : $defaultId;
            $args['result'] = 'success';
        } else {
            $message = '优惠券库存不足!';
        }
        return $this->_redirectCouponDetail($message, $args, $params);
    }

    /**
     * redirect to receive coupon page
     * @param $level,log level
     * @param $message,string
     * @param $args,array, args must include these key:mainDomain,couponId,id,memberId,result,channelId
     */
    private function _redirectCouponDetail($message, $args, $params)
    {
        extract($args);
        $url = $mainDomain . '?couponId=' . $couponId . '&id=' . $id
            . '&memberId=' . $memberId . '&result=' . $result . '&message='
            . $message . '&channelId=' . $channelId;
        LogUtil::info(['message' => $message, 'url' => $url, 'params' => $params], 'product');
        return $this->redirect($url);
    }
}
