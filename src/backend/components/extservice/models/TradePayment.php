<?php
namespace backend\components\extservice\models;

use Yii;
use MongoId;
use yii\web\BadRequestHttpException;
use backend\modules\channel\models\TradePayment as ModelPayment;
use backend\utils\ValidatorUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;

/**
 * Channel for extension
 * @author Rex Chen
 */
class TradePayment extends BaseComponent
{

    /**
     * Unified Order, the order must contain `orderNumber`,
     *     `shallCount`, `payMode`, `user`, `timeExpire`
     * @param  ObjectId $accountId
     * @param  array $order  the detail of trade order.
     * @return bool
     */
    public function create($order)
    {
        $requiredFields = [
            'userIp', 'subject', 'orderNumber', 'expectedAmount',
            'realAmount', 'payMode', 'timeExpire', 'openId',
            'user' => ['memberId'],
        ];
        ValidatorUtil::fieldsRequired($order, $requiredFields);

        ModelPayment::avoidDuplicate($order['orderNumber']);

        if (!ModelPayment::isAlreadyPrepay($order['orderNumber'])) {
            if (!ModelPayment::create($this->accountId, $order)) {
                throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
            }
        } else {
            ModelPayment::updatePayment($this->accountId, $order);
        }

        $totalFee = round(floatval($order['realAmount']) * 100);

        try {
            $reponse = Yii::$app->tradeService->unifiedOrder(
                $order['subject'],
                (string)new MongoId(),
                $totalFee,
                MongodbUtil::MongoDate2msTimeStamp($order['timeExpire']),
                $order['userIp'],
                $this->accountId,
                $order['openId'],
                $order['metadata']
            );
            return $reponse;
        } catch (yii\base\Exception   $e) {
            LogUtil::error(['message' => 'Create wechat order occurs a error', 'error' => $e->getMessage()], 'trade');
        }
    }


    public function getTransactionId($orderNumber)
    {
        return ModelPayment::getTransactionId($orderNumber, $this->accountId);
    }

    public function isPaid($orderNumber)
    {
        return ModelPayment::isPaid($this->accountId, $orderNumber);
    }

    public function getPaymentInfo($orderNumber)
    {
        $condition = [
            'orderNumber' => $orderNumber,
            'accountId'   => $this->accountId,
            'status'      => ModelPayment::STATUS_PAID
        ];
        return ModelPayment::findOne($condition);
    }

    public function offlinePayment($paymentInfo)
    {
        $requiredFields = [
            'user' => ['name', 'telephone'],
            'orderNumber', 'payMode', 'expectedAmount',
            'paymentTime', 'subject'
        ];
        if (ValidatorUtil::fieldsRequired($paymentInfo, $requiredFields, false)) {
            return ModelPayment::offlinePayment($paymentInfo, $this->accountId);
        }
        return false;
    }

    public function getPaymentUser($orderNumber)
    {
        $payment = $this->getPaymentInfo($orderNumber);
        if (!empty($payment)) {
            return $payment['user'];
        }
    }
}
