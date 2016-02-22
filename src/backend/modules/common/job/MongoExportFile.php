<?php
namespace backend\modules\common\job;

use Yii;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\utils\LogUtil;

class MongoExportFile
{
    public function perform()
    {
        $args = $this->args;

        /**
         * @param $header, array. key is used to map the data to export,value is file title
         * @param $condition, string. it is a serialize string.example serialize(array())
         * @param $key, string, file name
         * @param collection, string, collection name
         * @param $sort, array. example:['createdAt':-1]
         * @param $fields, string. it is used as fields for mongoexport
         * @param $classFunction, string.example:\backend\modules\product\models\CampaignLog::preProcessRedeemedCodeData
         * @param $params, array, it is a extra data as params in preProcessRedeemedCodeData
         */

        if (empty($args['header']) || empty($args['condition'])
            || empty($args['key']) || empty($args['accountId'])
            || empty($args['collection']) || empty($args['fields'])
            || empty($args['classFunction']) || !isset($args['params'])) {
            LogUtil::error(['message' => 'missing params when create job for export file', 'args' => $args]);
            return false;
        }

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = ExcelUtil::processCondition(unserialize($args['condition']));

        if (!isset($args['sort'])) {
            $args['sort'] = [];
        }
        ExcelUtil::exportWithMongo($args['collection'], $args['fields'], $filePath, $condition, $args['sort']);
        if (!file_exists($filePath)) {
            LogUtil::error(['message' => 'Can not find this file', 'fileName' => $filePath, 'condition' => $condition, 'args' => $args], 'resque');
            return false;
        }
        ExcelUtil::processRowsData($args['header'], $filePath, $args['classFunction'], $args['params']);
        $newFilePath = ExcelUtil::getDownloadFile($filePath);
        $hashKey = ExcelUtil::setQiniuKey($newFilePath, $fileName);

        @unlink($filePath);
        @unlink($newFilePath);

        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
