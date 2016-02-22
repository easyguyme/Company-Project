<?php
namespace backend\modules\product\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;
use backend\models\User;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\PromotionCode;
use backend\utils\MongodbUtil;
use backend\utils\ExcelUtil;
use backend\models\Message;

class ExportRedeemPromotionCode
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['header']) || empty($args['key']) || empty($args['accountId']) || empty($args['condition'])) {
            ResqueUtil::log(['status' => 'fail to export code', 'message' => 'missing params', 'args' => $args]);
            return false;
        }

        $header = $args['header'];
        //set the language
        Yii::$app->language = empty($args['language']) ? LanguageUtil::DEFAULT_LANGUAGE : $args['language'];

        $fileName = $args['key'];
        $filePath = ExcelUtil::getFile($fileName, 'csv');
        $condition = unserialize($args['condition']);
        $object = CampaignLog::find();
        $classFunction = '\backend\modules\product\models\CampaignLog::preProcessRedeemedCodeData';

        ExcelUtil::processMultiData($header, $filePath, [], $condition, $object, $classFunction, ['changeTostring' =>[]]);
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
