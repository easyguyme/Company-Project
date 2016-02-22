<?php
namespace backend\modules\product\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\models\Account;
use backend\modules\product\models\GoodsExchangeLog;
use backend\modules\product\models\PromotionCode;
use backend\models\Message;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;

class ExportGoodsExchangeLog
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['language']) || empty($args['header']) || empty($args['key']) || empty($args['accountId']) || empty($args['condition'])) {
            ResqueUtil::log(['status' => 'fail to export code', 'message' => 'missing params', 'args' => $args]);
            return false;
        }
        //set the language
        Yii::$app->language = empty($args['language']) ? LanguageUtil::DEFAULT_LANGUAGE : $args['language'];

        $header = $args['header'];

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = unserialize($args['condition']);
        $object = GoodsExchangeLog::find();
        $classFunction = '\backend\modules\product\models\GoodsExchangeLog::preProcessExportData';
        $backendUser = Account::findByPk(new \MongoId($args['accountId']));

        ExcelUtil::processMultiData($header, $filePath, $backendUser, $condition, $object, $classFunction, ['changeTostring' =>['goods']]);
        $hashKey = ExcelUtil::setQiniuKey($filePath, $fileName);
        if ($hashKey) {
            //notice frontend the job is finished
            \Yii::$app->tuisongbao->triggerEvent(Message::EVENT_EXPORT_FINISH, ['key' => $fileName], [Message::CHANNEL_GLOBAL . $args['accountId']]);
            return true;
        } else {
            return false;
        }
    }
}
