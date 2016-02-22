<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\utils\BulkSmsUtil;
use backend\utils\LogUtil;

class BulkSms
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['condition']) || empty($args['smsName'])) {
            ResqueUtil::log(['status' => 'fail to send early bird sms', 'message' => 'missing params', 'args' => $args]);
            LogUtil::error(['message'=>'missing params in job', 'args' => $args], 'bulkSms');
        }

        $condition = unserialize($args['condition']);
        $smsRecordId = new \MongoId($args['smsRecord']);
        $smsData = null;

        switch ($args['smsName']) {
            case 'cny_winners':
                $smsData = LuckyDrawWinner::preProcessCnyWinnerSmsData($condition);
                break;
            default: break;
        }

        BulkSmsUtil::sendSms($smsData, $args['smsName'], $smsRecordId, $condition['accountId']);
    }
}
