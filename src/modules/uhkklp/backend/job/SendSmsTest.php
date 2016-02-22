<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\utils\BulkSmsUtil;
use backend\utils\LogUtil;
use backend\utils\MessageUtil;
use backend\modules\uhkklp\models\BulkSmsLog;
use backend\modules\uhkklp\models\BulkSmsFailed;
use backend\modules\uhkklp\models\SmsResultModel;
use backend\modules\uhkklp\models\BulkSmsSuccess;

class SendSmsTest
{
    public function perform()
    {
        $args = $this->args;

       if (empty($args['testNumber']) || empty($args['testContent']) || empty($args['accountId'])) {
            ResqueUtil::log(['status' => 'fail to send sms', 'message' => 'missing params', 'args' => $args]);
            LogUtil::error(['message'=>'missing params in job', 'args' => $args], 'TestSendSms');
        }

        $testNumber = $args['testNumber'];
        $testContent = $args['testContent'];
        $accountId = $args['accountId'];
        LogUtil::error(date('Y-m-d h:i:s') . ' testNumber: ' . $testNumber);
        LogUtil::error(date('Y-m-d h:i:s') . ' testContent: ' . $testContent);
        try {
                if (!empty($args['testNumber']) && !empty($args['testContent']))
                {
                    // $response = MessageUtil::sendMobileMessage($testNumber, $testContent, $accountId);
                    $response = MessageUtil::sendMobileMessage($testNumber, $testContent);
                    if (!$response) {
                        LogUtil::error(date('Y-m-d h:i:s') . ' failed... ');
                        LogUtil::error(['message'=>'sendSmsTest失敗', 'mobile'=>$testNumber, 'SMSContent'=>$testContent], 'SendSmsTest');
                    } else {
                        LogUtil::error(date('Y-m-d h:i:s') . ' success.. ');
                        LogUtil::error(['message'=>'sendSmsTest成功', 'mobile'=>$testNumber, 'SMSContent'=>$testContent], 'SendSmsTest');
                    }
                    unset($response);
                } else {
                    LogUtil::error(date('Y-m-d h:i:s') . 'param为空.');
                }
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'TestSms發送失敗', 'error'=>$e], 'TestSms');
            throw $e;
        }
    }
}
