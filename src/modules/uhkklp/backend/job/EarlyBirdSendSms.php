<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\utils\LogUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;

class EarlyBirdSendSms
{
    public function perform()
    {

        $args = $this->args;

        if (empty($args['condition'])) {
            ResqueUtil::log(['status' => 'fail to send early bird sms', 'message' => 'missing params', 'args' => $args]);
            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'message' => 'missing params in job', 'args' => $args], 'earlybird');
        }

        $condition = unserialize($args['condition']);
        $smsRecord = new \MongoId($args['smsRecord']);

        if ($args['smsTag'] == 'sms_four') {
            EarlybirdSmsUtil::sendSmsByExchangeGoodsScore($condition, $args['smsTag'], $smsRecord);
        } else {
            EarlybirdSmsUtil::sendSmsBycondition($condition, $args['smsTag'], $smsRecord);
        }
    }
}
