<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use yii\mongodb\Query;
use backend\utils\LogUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\models\SmsModel;
use backend\modules\uhkklp\models\SmsRecord;

class ExportSmsTemplate
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['key']) || empty($args['header']) || empty($args['condition'])) {
            ResqueUtil::log(['status' => 'fail to export sms model record', 'message' => 'missing params', 'args' => $args]);
            return false;
        }

        $condition = unserialize($args['condition']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $query = new Query();
        $records = $query->from('uhkklpSmsRecord')
            ->select(['importResultList'])
            ->where($condition)
            ->all();

        // LogUtil::error(date('Y-m-d h:i:s') . ' $records....: ' . json_encode($records[0]["importResultList"]));

        // LogUtil::error(date('Y-m-d h:i:s') . ' $records....: ' . count($records[0]["importResultList"]));

        $rows = $records[0]["importResultList"];

        // LogUtil::error(date('Y-m-d h:i:s') . ' $content....: ' . count($rows));

        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export sms model', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }
    }
}
