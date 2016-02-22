<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\BulkSmsLog;

class ExportBulkSmsRecord
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header']) || empty($args['type']) || empty($args['smsRecordId'])) {
            ResqueUtil::log(['status' => 'fail to export bulk sms record', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $accountId = new \MongoId($args['accountId']);
        $smsRecordId = new \MongoId($args['smsRecordId']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = null;
        if ($args['type'] == 'all') {
            $rows = BulkSmsLog::preProcessBulkSmsRecordData($smsRecordId, $accountId);
        } else if ($args['type'] == 'faild') {
            // 暂时还没用到
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
