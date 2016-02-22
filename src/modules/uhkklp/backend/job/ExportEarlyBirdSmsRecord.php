<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use backend\modules\member\models\Member;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;
use backend\modules\uhkklp\models\EarlyBirdSmsDetail;
use backend\modules\uhkklp\models\EarlyBirdSmsFailed;

class ExportEarlyBirdSmsRecord
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header']) || empty($args['smsName'])) {
            ResqueUtil::log(['status' => 'fail to export early bird sms details', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $accountId = new \MongoId($args['accountId']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = null;
        if ($args['smsName'] == 'sms_four') {
            $rows = EarlyBirdSmsDetail::preProcessEarlyBirdSmsDetails($args['smsName'], $accountId);
        } else {
            $rows = EarlyBirdSmsFailed::preProcessSendFailedData($args['smsName'], $accountId);
        }

        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export early bird sms details', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }

    }
}
