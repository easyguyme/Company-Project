<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use yii\helpers\FileHelper;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\ActivityUser;

class ExportPrizeStatistic
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header'])) {
            ResqueUtil::log(['status' => 'fail to export slotgame prize statistic', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $accountId = new \MongoId($args['accountId']);
        $header = $args['header'];

        $condition = unserialize($args['condition']);

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $rows = ActivityUser::preProcessPrizeStatisticData($condition);
        ExcelUtil::exportCsv($header, $rows, $filePath, 1);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export slotgame prize statistic', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }

    }
}
