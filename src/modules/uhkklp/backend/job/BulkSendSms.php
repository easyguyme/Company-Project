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

class BulkSendSms
{
    public function perform()
    {
        $args = $this->args;

       if (empty($args['data']) || empty($args['accountId']) || empty($args['modelContent']) || empty($args['smsBatch'])) {
            ResqueUtil::log(['status' => 'fail to send sms', 'message' => 'missing params', 'args' => $args]);
            LogUtil::error(['message'=>'missing params in job', 'args' => $args], 'Sms');
        }

        $data = $args['data'];
        $accountId = $args['accountId'];
        $modelContent = $args['modelContent'];
        $smsBatch = $args['smsBatch'];

        $failureCount = 0;
        $successCount = 0;
        $totalCount = 0;

        try {
            if (!empty($data)) {
                foreach ($data as $sms) {
                    if ($sms['mobile'] != '')
                    {
                        $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['content'], $accountId);
                        // $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['content']);
                        BulkSmsLog::createSmsLog($sms['mobile'], $sms['content'], $response, $smsBatch, $accountId);
                        if (!$response) {
                            $failureCount++;
                            LogUtil::error(['message'=>'群發簡訊失敗', 'mobile'=>$sms['mobile'], 'SMSContent'=>$sms['content']], 'bulkSms');
                            BulkSmsFailed::createSmsFailed($sms['mobile'], $sms['content'], $smsBatch, $accountId);
                        } else {
                            $successCount++;
                            LogUtil::error(['message'=>'群發簡訊成功', 'mobile'=>$sms['mobile'], 'SMSContent'=>$sms['content']], 'bulkSms');
                            BulkSmsSuccess::createSmsSuccess($sms['mobile'], $sms['content'], $smsBatch, $accountId);
                        }
                        unset($response);
                    } else {
                        LogUtil::error(date('Y-m-d h:i:s') . '号码为空.');
                        $failureCount++;
                    }
                }
                $totalCount = $successCount + $failureCount;

                //record result
                $SmsResultModel = new SmsResultModel();
                $SmsResultModel->successRecord = $successCount;
                $SmsResultModel->failureRecord = $failureCount;
                $SmsResultModel->totalRecord = $totalCount;
                $SmsResultModel->smsBatch = $smsBatch;
                $SmsResultModel->accountId = $accountId;
                $SmsResultModel->modelContent = $modelContent;
                $SmsResultModel->save();
            }
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'Sms發送失敗', 'error'=>$e], 'sms');
            throw $e;
        }
    }
}
