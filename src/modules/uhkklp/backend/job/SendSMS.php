<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\utils\BulkSmsUtil;
use backend\utils\LogUtil;
use backend\utils\MessageUtil;
use backend\modules\uhkklp\models\BulkSmsLog;
use backend\modules\uhkklp\models\SmsRecord;
use backend\modules\uhkklp\models\BulkSmsFailed;
use backend\modules\uhkklp\models\SmsResultModel;
use backend\modules\uhkklp\models\BulkSmsSuccess;

class SendSMS
{
    public function perform()
    {
        LogUtil::error(date('Y-m-d h:i:s') . ' in job.. ');
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
                    // LogUtil::error(date('Y-m-d h:i:s') . ' $mobile: ' . $sms['mobile']);
                    // LogUtil::error(date('Y-m-d h:i:s') . ' $content: ' . $sms['content']);
                    if ($sms['mobile'] != '')
                    {
                        $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['content'], $accountId);
                        // $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['content']);
                        BulkSmsLog::createSmsLog($sms['mobile'], $sms['content'], $response, $smsBatch, $accountId);
                        if (!$response) {
                            // LogUtil::error(date('Y-m-d h:i:s') . ' fail.. ');
                            $failureCount++;
                            LogUtil::error(['message'=>'群發sms失敗', 'mobile'=>$sms['mobile'], 'SMSContent'=>$sms['content']], 'bulkSms');
                            BulkSmsFailed::createSmsFailed($sms['mobile'], $sms['content'], $smsBatch, $accountId);
                        } else {
                            // LogUtil::error(date('Y-m-d h:i:s') . ' success.. ');
                            $successCount++;
                            LogUtil::error(['message'=>'群發sms成功', 'mobile'=>$sms['mobile'], 'SMSContent'=>$sms['content']], 'bulkSms');
                            BulkSmsSuccess::createSmsSuccess($sms['mobile'], $sms['content'], $smsBatch, $accountId);
                        }
                        unset($response);
                    } else {
                        LogUtil::error(date('Y-m-d h:i:s') . 'empty number.');
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

                //modify isSend flag
                $condition = ['smsBatch' => $smsBatch];
                $SmsRecord = SmsRecord::findOne($condition);
                $SmsRecord->isSend = true;
                $result = $SmsRecord->save();
            }
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'Sms發送失敗', 'error'=>$e], 'sms');
            throw $e;
        }
    }
}
