<?php
namespace backend\modules\common\job;

use Yii;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\utils\LogUtil;

class ExportDataFromWeconnect
{
    public function perform()
    {
        $args = $this->args;

        /**
         * @param $header, array. key is used to map the data to export,value is file title
         * @param $condition, string. it is a serialize string.example serialize(array())
         * @param $key, string, file name
         * @param $classFunction, string.example:\backend\modules\product\models\CampaignLog::preProcessRedeemedCodeData
         */

        if (empty($args['header']) || empty($args['condition'])
            || empty($args['key']) || empty($args['accountId'])
            || empty($args['classFunction'])) {
            LogUtil::error(['message' => 'missing params when create job for export file', 'args' => $args]);
            return false;
        }

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $resutl = call_user_func_array($args['classFunction'], [unserialize($args['condition']), $args['header'], $filePath]);

        if (false == $resutl) {
            return false;
        }
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);

        @unlink($filePath);

        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
