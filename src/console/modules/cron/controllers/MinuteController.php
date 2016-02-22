<?php
namespace console\modules\cron\controllers;

use Yii;
use yii\console\Controller;
use backend\utils\TimeUtil;
use backend\models\Goods;
use backend\models\StoreGoods;
use backend\models\Account;
use backend\models\PendingClient;
use backend\modules\product\models\Campaign;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\ChatConversation;

/**
 * Those command which is perform once a minute.
 */
class MinuteController extends Controller
{
    /**
    * Execute all commands that is once a minute.
    */
    public function actionIndex()
    {
        $this->actionGoodsSale();
        $this->actionStoreGoodsSale();
        $this->actionCampaignExpired();
        $this->actionCleanOffline();
        $this->actionMembershipDiscount();
    }

    /**
    * Update the status of goods for goods module.
    */
    public function actionGoodsSale()
    {
        $result = Goods::updateAll(
            ['$set' => ['status' => Goods::STATUS_ON]],
            [
                'status' => Goods::STATUS_OFF,
                'onSaleTime' => ['$lte' => new \MongoDate(strtotime('+1 minute'))]
            ]
        );
        echo $result;
    }

    /**
    * Update the status of membershipDiscount for membershipDiscount module.
    */
    public function actionMembershipDiscount()
    {
        Yii::$app->job->create('backend\modules\product\job\DisableExpiredCoupon', []);
    }

    /**
    * Update the status of goods for store module.
    */
    public function actionStoreGoodsSale()
    {
        $result = StoreGoods::updateAll(
            ['$set' => ['status' => StoreGoods::STATUS_ON]],
            [
                'status' => StoreGoods::STATUS_OFF,
                'onSaleTime' => ['$lte' => new \MongoDate(strtotime('+1 minute'))]
            ]
        );
        echo $result;
    }

    /**
     * Update the status of campaign for product module.
     */
    public function actionCampaignExpired()
    {
        echo Campaign::expiredByTime(new \MongoDate(strtotime('+1 minute')));
    }
}
