<?php
namespace backend\modules\channel\events;

use Yii;
use yii\web\BadRequestHttpException;
use MongoId;
use MongoDate;
use backend\components\BaseEvent;
use backend\utils\LogUtil;
use backend\modules\channel\models\TradePayment;
use backend\exceptions\InvalidParameterException;
use backend\utils\ValidatorUtil;
use backend\utils\MongodbUtil;

class WeconnectEvent extends BaseEvent
{
    public function handle($data)
    {
        parent::handle($data);
        # handle the wechat message
        LogUtil::info(['channel weconnect event' => $data], 'channel-webhook');
        if (empty($data)) {
            throw new BadRequestHttpException(Yii::t('channel', 'parameter_format_error'));
        }
        $data = $data['data'];
        $requiredParameters = ['outTradeNo', 'tradeNo', 'tradeStatus', 'quncrmAccountId', 'metadata' => ['orderNumber']];
        ValidatorUtil::fieldsRequired($data, $requiredParameters);

        $tradePayment = TradePayment::findOne(['orderNumber' => $data['metadata']['orderNumber']]);

        if (empty($tradePayment)) {
            //throw new InvalidParameterException(Yii::t('common', 'data_error'));
            return;
        }
        LogUtil::info(['tradePayment' => $tradePayment->toArray()], 'channel-webhook');

        $memberId = $tradePayment['user']['memberId'];
        $couponCode = $tradePayment['couponCode'];
        LogUtil::info(['memberId' => $memberId, 'couponCode' => $couponCode], 'channel-webhook');
        if ($data['tradeStatus'] == 'PAY_SUCCESS') {
            $tradePayment->status = TradePayment::STATUS_PAID;
            $tradePayment->realAmount = intval($data['totalFee']) / 100;
            LogUtil::info(['pay' => 'success'], 'channel-webhook');
            // Make coupon used
            if (!empty($couponCode)) {
                LogUtil::info(['used coupon' => $couponCode], 'channel-webhook');
                $accountId = new MongoId($data['quncrmAccountId']);
                Yii::$app->service->setAccountId($accountId)->coupon->makeUsed($memberId, $couponCode);
            }
        } else if ($data['tradeStatus'] == 'PAY_ERROR') {
            $tradePayment->status = TradePayment::STATUS_FAILED;
        }
        $tradePayment->paymentTime = MongodbUtil::msTimetamp2MongoDate($data['paymentTime']);
        $tradePayment->transactionId = $data['tradeNo'];
        return $tradePayment->save(true, ['paymentTime', 'status', 'transactionId', 'realAmount']);
    }
}
