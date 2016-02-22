<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\LuckyDrawWinner;

class ExportLuckyDrawWinners
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header']) || empty($args['activityName'])) {
            ResqueUtil::log(['status' => 'fail to export lucky draw winners record', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        // $accountId = new \MongoId($args['accountId']);
        $condition = unserialize($args['condition']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = array();
        if ($args['activityName'] == 'cny') {
            $rows = LuckyDrawWinner::preProcessCnyWinnerData($condition);
        }

        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export lucky draw winners record', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }

    }
}