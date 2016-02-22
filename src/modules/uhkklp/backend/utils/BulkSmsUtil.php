<?php
namespace backend\modules\uhkklp\utils;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\utils\MessageUtil;
use backend\utils\LogUtil;
use backend\models\Token;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\models\BulkSmsRecord;
use backend\modules\uhkklp\models\BulkSmsFailed;
use backend\modules\uhkklp\models\KlpAccountSetting;
use backend\modules\uhkklp\models\BulkSmsLog;

class BulkSmsUtil
{
    const CNY_WINNERS_SMS_TEMPLATE = '%username%恭喜你抽中%awardName%,到便利商店買杯咖啡,休息一下吧!'; //TODO

    /**
     * @param $data array eg: [['mobile'=>'0912345678', 'smsContent'=>'恭喜中奖了'], ...]
     * @param $smsName string eg: 'cny_winners'
     * @param $smsRecord MongoId
     */
    public static function sendSms($data, $smsName, $smsRecordId, $accountId)
    {
        BulkSmsRecord::updateProcessById($smsRecordId, 1);  // 正在發送
        try {
            if (!empty($data)) {
                foreach ($data as $sms) {
                    $mobile = self::processSmsMobile($accountId, $sms['mobile']);
                    $response = MessageUtil::sendMobileMessage($mobile, $sms['smsContent'], $accountId);
                    BulkSmsLog::createSmsLog($sms['mobile'], $sms['smsContent'], $response, $smsRecordId, $accountId);
                    if (!$response) {
                        LogUtil::error(['message'=>'群發簡訊失敗', 'mobile'=>$mobile, 'SMSContent'=>$sms['smsContent']], 'bulkSms');
                        BulkSmsFailed::createSmsFailed($sms['mobile'], $sms['smsContent'], $smsRecordId, $accountId);
                    }
                    unset($response, $mobile);
                }
                BulkSmsRecord::updateProcessById($smsRecordId, 2);  // 發送完成
            }

        } catch (\Exception $e) {
            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'error'=>$e], 'earlybird');
            BulkSmsRecord::updateProcessById($smsRecordId, 3);  // 發送故障
            throw $e;
        }
    }

    public static function createSmsJob($condition, $operator, $smsName)
    {
        $totalCount = 0;
        $accountId = Token::getAccountId();

        if (!array_key_exists('accountId', $condition)) {
            $condition = array_merge($condition, ['accountId'=>$accountId]);
        }

        switch ($smsName) {
            case 'cny_winners':
                $smsTemplate = self::CNY_WINNERS_SMS_TEMPLATE;
                $totalCount = LuckyDrawWinner::find()->where($condition)->count();
                break;
            default: break;
        }

        $recordId = BulkSmsRecord::createSmsRecord($operator, $smsName, $smsTemplate, $totalCount);

        if (is_bool($recordId) && !$recordId) {
            throw new ServerErrorHttpException("發送失敗，請刷新頁面重試!");
        }

        $args = [
            'condition' => serialize($condition),
            'smsName' => $smsName,
            'smsRecord' => (string)$recordId
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\BulkSms', $args);

        if (!empty($jobId)) {
            return ['smsRecordId'=>(string)$recordId, 'count'=>$totalCount];
        } else {
            throw new ServerErrorHttpException("發送失敗，請刷新頁面重試!");
        }
    }

    /**
     * @param $smsRecordId MongoId
     * @param $accountId MongoId
     * @param $fileName string
     * @param $type string ('all' or 'failed')
     */
    public static function createExportSmsRecordJob($smsRecordId, $accountId, $fileName, $type='all')
    {
        $key = $fileName . date('YmdHis');
        $header = [
            'mobile' => '手機號碼',
            'smsContent' => '簡訊內容',
            'status' => '發送狀態',
            'createdAt' => '發送時間'
        ];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'accountId' => (string)$accountId,
            'type' => $type,
            'smsRecordId' => (string)$smsRecordId,
            'description' => 'Direct: export bulk sms record'
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportBulkSmsRecord', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    // $accountId: MongoId , $mobile: string
    public static function processSmsMobile($accountId, $mobile)
    {
        $site = KlpAccountSetting::getAccountSite($accountId);
        if ($site == 'TW') {
            // DO nothing
        } else if ($site == 'HK') {
            if (strlen($mobile) == 8) {
                $mobile = '852' . $mobile;
            }
        }
        unset($site);
        return $mobile;
    }
}
