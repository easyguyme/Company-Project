<?php
namespace console\modules\management\controllers;

use yii\console\Controller;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberLogs;
use backend\modules\member\models\ScoreHistory;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\GoodsExchangeLog;
use backend\modules\member\models\MemberStatistics;
use backend\models\ReMemberCampaign;
use backend\models\StatsCampaignProductCodeQuarterly;
use backend\models\StatsMemberCampaignLogDaily;
use backend\models\StatsMemberDaily;
use backend\modules\member\models\StatsMemberGrowthMonthly;
use backend\modules\member\models\StatsMemberGrowthQuarterly;
use backend\modules\member\models\StatsMemberMonthly;
use backend\models\StatsMemberPropAvgTradeQuarterly;
use backend\models\StatsMemberPropMonthly;
use backend\models\StatsMemberPropQuaterly;
use backend\models\StatsMemberPropTradeCodeAvgQuarterly;
use backend\models\StatsMemberPropTradeCodeQuarterly;
use backend\models\StatsMemberPropTradeQuarterly;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\models\Order;
use backend\modules\member\models\StatsMemberOrder;
use backend\modules\member\models\StatsOrder;
use backend\modules\product\models\MembershipDiscount;
use backend\modules\product\models\CouponLog;

/**
 *  Delete member info by account id
 */
class DeleteMemberInfoController extends Controller
{
    public function actionIndex($accountId)
    {
        if (empty($accountId)) {
            echo 'accountId is invaild' . PHP_EOL;
            exit();
        }

        $where['accountId'] = new \MongoId($accountId);
        // delete member info
        Member::getCollection()->remove($where);
        //delete MemberLogs
        MemberLogs::getCollection()->remove($where);
        //delete scoreHistory
        ScoreHistory::getCollection()->remove($where);
        //delete CampaignLog
        CampaignLog::getCollection()->remove($where);
        //delete GoodsExchangeLog
        GoodsExchangeLog::getCollection()->remove($where);
        //delete MemberStatistics
        MemberStatistics::getCollection()->remove($where);
        //delete ReMemberCampaign
        ReMemberCampaign::getCollection()->remove($where);
        //delete StatsCampaignProductCodeQuarterly
        StatsCampaignProductCodeQuarterly::getCollection()->remove($where);
        //delete StatsMemberCampaignLogDaily
        StatsMemberCampaignLogDaily::getCollection()->remove($where);
        //delete StatsMemberDaily
        StatsMemberDaily::getCollection()->remove($where);
        //delete StatsMemberGrowthMonthly
        StatsMemberGrowthMonthly::getCollection()->remove($where);
        //delete StatsMemberGrowthQuarterly
        StatsMemberGrowthQuarterly::getCollection()->remove($where);
        //delete StatsMemberMonthly
        StatsMemberMonthly::getCollection()->remove($where);
        //delete StatsMemberPropAvgTradeQuarterly
        StatsMemberPropAvgTradeQuarterly::getCollection()->remove($where);
        //delete StatsMemberPropMonthly
        StatsMemberPropMonthly::getCollection()->remove($where);
        //delete StatsMemberPropQuaterly
        StatsMemberPropQuaterly::getCollection()->remove($where);
        //delete StatsMemberPropTradeCodeAvgQuarterly
        StatsMemberPropTradeCodeAvgQuarterly::getCollection()->remove($where);
        //delete StatsMemberPropTradeCodeQuarterly
        StatsMemberPropTradeCodeQuarterly::getCollection()->remove($where);
        //delete StatsMemberPropTradeQuarterly
        StatsMemberPropTradeQuarterly::getCollection()->remove($where);
        //delete PromotionCodeAnalysis
        PromotionCodeAnalysis::getCollection()->remove($where);
        //delete order
        Order::getCollection()->remove($where);
        //delete stats member order
        StatsMemberOrder::getCollection()->remove($where);
        //delete stats order
        StatsOrder::getCollection()->remove($where);
        //delete MembershipDiscount
        MembershipDiscount::getCollection()->remove($where);
        //delete couponLog
        CouponLog::getCollection()->remove($where);

        echo 'delete data successful.' . PHP_EOL;
    }
}
