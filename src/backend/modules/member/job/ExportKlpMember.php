<?php
namespace backend\modules\member\job;

use Yii;
use backend\modules\member\models\MemberProperty;
use backend\models\Account;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;
use backend\utils\StringUtil;
use backend\models\Message;
use backend\utils\LanguageUtil;
use backend\models\Channel;
use yii\helpers\FileHelper;
use backend\utils\LogUtil;

class ExportKlpMember
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['key']) || empty($args['header']) || empty($args['collection']) || empty($args['fields'])) {
            LogUtil::info(['message' => 'fail to export klp member, missing params', 'args' => $args], 'resque');
            return false;
        }

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = ExcelUtil::processCondition(unserialize($args['condition']));

        ExcelUtil::exportWithMongo($args['collection'], $args['fields'], $filePath, $condition);

        $classFunction = '\backend\modules\member\models\Member::preProcessKlpMemberData';
        $headerKeys = array_keys($args['header']);

        ExcelUtil::processRowsData($args['header'], $filePath, $classFunction, $headerKeys);

        $newFilePath = ExcelUtil::getDownloadFile($filePath);
        $downloadFilePath = self::getFile($fileName);
        copy($newFilePath, $downloadFilePath);

        @unlink($newFilePath);
        @unlink($filePath);
        //notice frontend the job is finished
        Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
    }

    public static function getFile($fileName, $fileType = 'csv')
    {
        //make sure the file name is unqiue
        $fileName = Yii::getAlias('@frontend') . '/web/download/' . $fileName . '.' . strtolower($fileType);
        $filePath = dirname($fileName);
        if (!is_dir($filePath)) {
            FileHelper::createDirectory($filePath, 0777, true);
        }
        return $fileName;
    }
}
