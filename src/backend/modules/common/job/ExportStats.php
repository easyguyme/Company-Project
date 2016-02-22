<?php
namespace backend\modules\common\job;

use backend\modules\resque\components\ResqueUtil;
use backend\utils\ExcelUtil;
use backend\models\Message;
use backend\utils\LanguageUtil;

class ExportStats
{
    public function perform()
    {
        /**
         * @param $header,array @example Signup Summary ['month' => 'Month', 'channel' => 'Channel', 'number' => 'Signup Number']
         * @param $key,string @example Signup Summary 'Signup Summary_20150707'
         * @param $condition, array @example ['accountId' => ObjectId('55910a3ad6f97fad0b8b4568'), 'date' => ['$lge' => '2015-06-01', '$lte' => '2015-07-01']]
         * @param $classFunction, string @example '\backend\modules\member\models\StatsMemberMonthly::preProcessData'
         */
        $args = $this->args;
        \Yii::$app->language = empty($args['language']) ? LanguageUtil::DEFAULT_LANGUAGE : $args['language'];
        if (empty($args['header'] || empty($args['key'])
            || empty($args['condition']) || empty($args['classFunction']))) {
            ResqueUtil::log(['status' => 'fail to export stats', 'message' => 'missing params', 'args' => $args]);
            return false;
        }

        $header = $args['header'];
        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $classFunction = $args['classFunction'];
        $condition = unserialize($args['condition']);

        ExcelUtil::processData($header, $filePath, $classFunction, $condition);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            \Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $condition['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
