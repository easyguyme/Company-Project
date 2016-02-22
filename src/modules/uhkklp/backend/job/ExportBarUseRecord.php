<?php
namespace backend\modules\uhkklp\job;

use Yii;
use backend\models\Message;
use backend\utils\ExcelUtil;
use yii\helpers\FileHelper;
use backend\modules\resque\components\ResqueUtil;
use backend\modules\uhkklp\models\ActivityUser;

class ExportBarUseRecord
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header'])) {
            ResqueUtil::log(['status' => 'fail to export slotgame useRecord', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        $condition = unserialize($args['condition']);
        $activityId = $condition['activityId'];
        $condition = ['activityId'=>$activityId];

        $accountId = new \MongoId($args['accountId']);
        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $object = ActivityUser::find();
        if (empty($object)) {
            ResqueUtil::log(['status' => 'fail to export slotgame useRecord', 'message' => 'no data found in DB', 'args' => $args]);
            return false;
        }

        $classFunction = '\backend\modules\uhkklp\models\ActivityUser::preProcessBarUseRecordData';
        ExcelUtil::processMultiData($header, $filePath, $args, $condition, $object, $classFunction);

        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            ResqueUtil::log(['status' => 'fail to export slotgame useRecord', 'message' => 'fail to setQiniuKey', 'filePath' => $filePath]);
            return false;
        }

    }
}
