<?php
namespace backend\modules\product\job;

use backend\modules\resque\components\ResqueUtil;
use backend\modules\product\models\Product;
use backend\modules\product\models\PromotionCode;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\models\Message;
use Yii;

class ExportPromotionCode
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['status']) || empty($args['header'])
            || empty($args['key']) || empty($args['accountId'])
            || empty($args['sku'])) {
            LogUtil::error(['message' => 'Faild to export code, missing params', 'args' => $args], 'resque');
            return false;
        }

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = ExcelUtil::processCondition(unserialize($args['condition']));
        $baseData = [
            'sku' => $args['sku'],
            'status' => $args['status'],
        ];

        ExcelUtil::exportWithMongo('promotionCode', $args['fields'], $filePath, $condition);
        $classFunction = '\backend\modules\product\models\PromotionCode::preProcessCodeData';
        if (!file_exists($filePath)) {
            return false;
        }
        ExcelUtil::processRowsData($args['header'], $filePath, $classFunction, $baseData);
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
