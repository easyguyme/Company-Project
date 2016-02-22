<?php
namespace backend\modules\marketing\controllers;

use Yii;
use MongoId;
use MongoDate;
use backend\models\Token;
use backend\modules\product\models\Coupon;
use backend\modules\member\models\Member;
use backend\modules\product\models\CouponLog;
use backend\modules\product\models\StatsCouponLogDaily;
use backend\modules\product\models\MembershipDiscount;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\utils\TimeUtil;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

class CouponLogController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\CouponLog';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        if (!isset($params['status']) || empty($params['status'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }

        $accountId = $this->getAccountId();
        return CouponLog::search($params, $accountId);
    }

    public function actionStatsCoupon()
    {
        $params = $this->getQuery();

        if (empty($params['id']) || !isset($params['startTime']) || !isset($params['endTime'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $id = new MongoId($params['id']);
        $couponLog = Coupon::findOne(["_id" => $id]);
        if (empty($couponLog)) {
            throw new BadRequestHttpException(Yii::t('product', 'membershipDiscount_is_deleted'));
        }

        //turn unix timestamp to string
        $startTime = TimeUtil::msTime2String($params['startTime'], 'Y-m-d');
        $endTime = TimeUtil::msTime2String($params['endTime'], 'Y-m-d');

        $couponPeriodInfo = StatsCouponLogDaily::getCouponLogStats($id, $startTime, $endTime);
        ArrayHelper::multisort($couponPeriodInfo, 'date', SORT_ASC);

        $dateCouPonStats = ArrayHelper::index($couponPeriodInfo, 'date');
        $item = $redeemedNum = $recievedNum = $date = [];

        $startDate = strtotime($startTime);
        $endDate = strtotime($endTime);

        if (!empty($couponPeriodInfo) && count($couponPeriodInfo) > 0) {
            while ($startDate <= $endDate) {
                $dateStr = date('Y-m-d', $startDate);
                if (!empty($dateCouPonStats[$dateStr])) {
                    $date[] = $dateStr;
                    $recievedNum[] = $dateCouPonStats[$dateStr]['recievedNum'];
                    $redeemedNum[] = $dateCouPonStats[$dateStr]['redeemedNum'];
                } else {
                    $date[] = $dateStr;
                    $recievedNum[] = 0;
                    $redeemedNum[] = 0;
                }
                $startDate = strtotime($dateStr . ' +1 day');
            }
        }

        $item = [
            'date' => $date,
            'count' => [
                'recievedNum' => $recievedNum,
                'redeemedNum' => $redeemedNum,
            ]
        ];
        return $item;
    }

    public function actionStatsTotalCoupon()
    {
        $params = $this->getQuery();

        if (empty($params['id'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $id = new MongoId($params['id']);
        $coupon = Coupon::findOne(["_id" => $id]);

        if (empty($coupon)) {
            throw new BadRequestHttpException(Yii::t('product', 'membershipDiscount_is_deleted'));
        }
        $couponTotalInfo = StatsCouponLogDaily::getCouponLogTotalStats($id);
        $item = empty($couponTotalInfo[0]) ? [] : $couponTotalInfo[0];
        return $item ;
    }
}
