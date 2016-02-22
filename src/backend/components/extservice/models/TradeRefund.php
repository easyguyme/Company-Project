<?php
namespace backend\components\extservice\models;

use Yii;
use yii\web\BadRequestHttpException;
use backend\modules\channel\models\TradeRefund as ModelRefund;
use backend\utils\ValidatorUtil;

/**
 * Channel for extension
 * @author Rex Chen
 */
class TradeRefund extends BaseComponent
{

    public function refund($refundInfo)
    {
        $requiredFields = [
            'subject', 'orderNumber', 'expectedAmount', 'realAmount',
            'admin', 'user', 'refundMode'
        ];
        ValidatorUtil::fieldsRequired($refundInfo, $requiredFields);

        // TODO  1. Call weconnect to refund

        // Save refund message to tradeRefund.
        return ModelRefund::refund($this->accountId, $refundInfo);
    }
}
