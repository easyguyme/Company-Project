<?php
namespace backend\modules\channel\job;

use backend\modules\resque\components\ResqueUtil;
use backend\utils\ExcelUtil;
use yii\helpers\ArrayHelper;
use backend\models\Message;
use Yii;

/**
* Job for export qrcode info
*/
class ExportQrcodeInfo
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['header']) || empty($args['key']) || empty($args['channelId']) || empty($args['qrcodeId']) || empty($args['condition'])) {
            ResqueUtil::log(['message' => 'missing param in export qrcode info', 'args' => $args]);
            return false;
        }

        $condition = unserialize($args['condition']);

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');

        $classFunction = '\backend\models\Qrcode::preProcessData';
        $condition = [
            'condition' => $condition,
            'channelId' => $args['channelId'],
            'qrcodeId' => $args['qrcodeId'],
        ];
        ExcelUtil::processData($args['header'], $filePath, $classFunction, $condition);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
