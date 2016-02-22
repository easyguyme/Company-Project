<?php
namespace backend\modules\member\controllers;

use Yii;
use MongoId;
use backend\models\Token;
use backend\models\Order;
use backend\models\Store;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\StatsMemberOrder;
use backend\modules\member\models\StatsOrder;
use backend\utils\MongodbUtil;
use backend\components\Controller;

class OrderController extends Controller
{
    public $modelClass = 'backend\models\Order';

    public function actionIndex()
    {
        $params = $this->getQuery();
        if (empty($params['memberId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $params['status'] = Order::ORDER_STATUS_FINISHED;

        $page = intval($this->getQuery('page', 1));
        $perPage = intval($this->getQuery('per-page', 10));

        $items = Order::getStoreGoods($params, $accountId, $page, $perPage);
        
        $orders = [];
        foreach ($items as $item) {
            $item['id'] = (string)$item['_id'];
            $item['store'] = Store::findByPk($item['storeId']);
            $item['storeId'] = (string)$item['storeId'];
            $item['expectedPrice'] = number_format($item['expectedPrice'], 2);
            $item['totalPrice'] = number_format($item['totalPrice'], 2);
            $item['staff']['id'] = (string)$item['staff']['id'];
            $item['storeGoods']['id'] = (string)$item['storeGoods']['id'];
            $item['storeGoods']['price'] = number_format($item['storeGoods']['price'], 2);
            $item['storeGoods']['totalPrice'] = number_format($item['storeGoods']['totalPrice'], 2);
            $item['operateTime'] = MongodbUtil::MongoDate2String($item['operateTime'], 'Y-m-d H:i:s');
            $item['createdAt'] = MongodbUtil::MongoDate2String($item['createdAt'], 'Y-m-d H:i:s');

            unset($item['_id'], $item['updatedAt'], $item['accountId']);
            $orders[] = $item;
        }
        $items = $orders;

        $totalCount = Order::countStoreGoods($params, $accountId);

        $meta = [
            'totalCount' => $totalCount,
            'pageCount' => ceil($totalCount / $perPage),
            'currentPage' => $page,
            'perPage' => $perPage
        ];
        return ['items' => $items, '_meta' => $meta];
    }

    public function actionStats()
    {
        $memberId = $this->getQuery('memberId');
        if (empty($memberId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();
        $memberOrderStats = StatsMemberOrder::getByConsumerId($accountId, $memberId);
        $order = Order::getLastByConsumerId($accountId, $memberId);
        if (empty($order)) {
            $result = [
                'lastOperateTime' => '',
                'operateInterval' => null,
            ];
        } else {
            $lastDate = MongodbUtil::MongoDate2String($order->operateTime, 'Y-m-d');
            $result = [
                'lastOperateTime' => $lastDate,
                'operateInterval' => abs((strtotime(date('Y-m-d'))-strtotime($lastDate))/86400),
            ];
        }
        if (empty($memberOrderStats)) {
            $result['consumptionAmount'] = $result['recentConsumption'] = $result['consumption'] = $result['memberMaxConsumption'] = 0;
        } else {
                $result['consumptionAmount'] = round($memberOrderStats->consumptionAmount);
                $result['recentConsumption'] = $memberOrderStats->recentTransactionCount;
                $result['consumption'] = round($memberOrderStats->consumptionAmount / $memberOrderStats->transactionCount);
                $result['memberMaxConsumption'] = round($memberOrderStats->maxConsumption);
        }
        $orderStats = StatsOrder::getLatestByAccount($accountId);
        if (empty($orderStats)) {
            $result['consumptionAmountAvg'] = $result['recentConsumptionAvg'] = $result['consumptionAvg'] = $result['maxConsumption'] = 0;
        } else {
            $result['consumptionAmountAvg'] = round($orderStats->consumptionAmount / $orderStats->consumerCount);
            $result['recentConsumptionAvg'] = round($orderStats->recentTransactionTotal / $orderStats->consumerCount);
            $result['consumptionAvg'] = round($orderStats->consumptionAmount / $orderStats->transactionCount);
            $result['maxConsumption'] = round($orderStats->maxConsumptionTotal / $orderStats->consumerCount);
        }
        return $result;
    }
}
