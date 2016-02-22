<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\utils\LogUtil;
use backend\modules\uhkklp\models\PushMessageLog;

class ExportPushResult
{
    public function perform()
    {
        $args = $this->args;
        $messageId = $args['messageId'];
        $accoutnId = $args['accoutnId'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $header = [
            'messageId' => '消息ID',
            'messageContent' => '消息內容',
            'mobile' => '手機號',
            'deviceType' => '設備類型',
            'deviceId' => '設備ID',
            'result' => '推播結果'
        ];

        $rows = PushMessageLog::getResults($messageId);

        ExcelUtil::exportCsv($header, $rows, $filePath, 1);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $accoutnId]);
    }
}
