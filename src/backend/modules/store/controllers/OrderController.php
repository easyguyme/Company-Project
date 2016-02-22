<?php
namespace backend\modules\store\controllers;

use Yii;
use backend\models\Order;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\components\rest\RestController;

class OrderController extends RestController
{
    //pay way defind in offline
    const ORDER_PAY_WAY = 'manual';

    public $modelClass = 'backend\models\Order';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['update'], $actions['delete'], $actions['create']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        return Order::search($params, $accountId);
    }

    public function actionUpdate($id)
    {
        $id = new \MongoId($id);
        $params = $this->getParams();

        if (empty($params['status'])) {
            throw new BadRequestHttpException(Yii::t('store', 'status_missing'));
        }

        if (false === Order::checkOrderStatus($params['status'])) {
            throw new InvalidParameterException(Yii::t('store', 'status_invalid'));
        }

        $orderInfo = Order::findByPk($id);
        if (!empty($orderInfo) && $orderInfo->status == Order::ORDER_STATUS_WAITING) {
            $orderInfo->status = $params['status'];
            if (Order::ORDER_STATUS_FINISHED == $params['status']) {
                $orderInfo->payWay = self::ORDER_PAY_WAY;
            }
            $orderInfo->operateTime = new \MongoDate();
            if ($orderInfo->save()) {
                return $orderInfo;
            }
        }
        throw new ServerErrorHttpException(Yii::t('common', 'update_fail'));
    }
}
