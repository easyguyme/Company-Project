<?php
namespace backend\modules\product\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\models\User;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\PromotionCode;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;
use backend\utils\LogUtil;
use yii\helpers\Json;
use backend\models\Message;

class ExportPromotionCodeAnalysis
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['header']) || empty($args['condition'])
            || empty($args['key']) || empty($args['accountId'])
            || empty($args['collection']) || empty($args['sort'])
            || empty($args['fields']) || empty($args['classFunction'])
            || !isset($args['params'])) {
            LogUtil::error(['message' => 'missing params when create job for export file', 'args' => $args]);
            return false;
        }

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = ExcelUtil::processCondition(unserialize($args['condition']));

        ExcelUtil::exportWithMongo($args['collection'], $args['fields'], $filePath, $condition, $args['sort']);
        if (!file_exists($filePath)) {
            LogUtil::error(['message' => 'Can not find this file', 'fileName' => $filePath, 'condition' => $condition, 'args' => $args], 'resque');
            return false;
        }
        self::processProductInfoData($args['header'], $filePath, $args['classFunction'], $args['params']);
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

    private function processProductInfoData($header, $filePath, $classFunction, $params = [])
    {
        LogUtil::info(['message' => 'Begin to read csv file', 'fileName' => $filePath], 'resque');
        $fileInfos = fopen($filePath, "r");
        $newFilePath = ExcelUtil::getDownloadFile($filePath);

        $productNames = $infos = [];
        while (!feof($fileInfos)) {
            $fileInfo = Json::decode(fgets($fileInfos), true);
            if (!empty($fileInfo)) {
                if (in_array($fileInfo['productName'], $productNames) || empty($productNames)) {
                    $infos[] = $fileInfo;
                    $productNames[] = $fileInfo['productName'];
                } else {
                    $args = [$infos, $params];
                    self::writeCsv($classFunction, $args, $header, $newFilePath);
                    unset($infos, $productNames);
                    $productNames[] = $fileInfo['productName'];
                    $infos[] = $fileInfo;
                }
            }
        }

        if (!empty($infos)) {
            $args = [$infos, $params];
            self::writeCsv($classFunction, $args, $header, $newFilePath);
            unset($productNames, $infos);
        }
        fclose($fileInfos);
        LogUtil::info(['message' => 'End to read csv file and end to write file', 'fileName' => $filePath], 'resque');
    }

    private function writeCsv($classFunction, $args, $header, $newFilePath)
    {
        $data = call_user_func_array($classFunction, $args);
        ExcelUtil::exportCsv($header, [$data], $newFilePath, 1);
        unset($data);
    }
}
