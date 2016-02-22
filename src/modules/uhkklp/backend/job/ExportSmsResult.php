<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\models\BulkSmsFailed;
use backend\modules\uhkklp\models\BulkSmsSuccess;

//export failure sms
class ExportSmsResult
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['key']) || empty($args['header']) || empty($args['condition'])) {
            ResqueUtil::log(['status' => 'fail to export sms result record', 'message' => 'missing params', 'args' => $args]);
            return false;
        }

        $condition = unserialize($args['condition']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = BulkSmsFailed::find()->where($condition)->all();
        $rowsSuccess = BulkSmsSuccess::find()->where($condition)->all();
        $finalRows = array_merge($rows, $rowsSuccess);

        ExcelUtil::exportCsv($header, $finalRows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export sms result', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }
    }
}
