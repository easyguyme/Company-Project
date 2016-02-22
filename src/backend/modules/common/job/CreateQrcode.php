<?php
namespace backend\modules\common\job;

use Yii;
use MongoId;
use backend\utils\LogUtil;
use backend\utils\UrlUtil;

class CreateQrcode
{
    public function perform()
    {
        $args = $this->args;

        if (!isset($args['url']) || empty($args['model']) || empty($args['qrcodeType']) || empty($args['associatedId']) || empty($args['accountId'])) {
            LogUtil::error(['message' => 'Params missing', 'params' => $args], 'resque');
            return false;
        }

        if (empty($args['url'])) {
            $qrcode = Yii::$app->qrcode->create(UrlUtil::getDomain(), $args['qrcodeType'], $args['associatedId'], new MongoId($args['accountId']));
        } else {
            $qrcode = Yii::$app->qrcode->create($args['url'], $args['qrcodeType'], $args['associatedId'], new MongoId($args['accountId']), false);
        }

        //update qrcode info
        $qrcodeItem = [
            'id' => $qrcode->_id,
            'qiniuKey' => $qrcode->qiniuKey,
        ];

        if (false == $args['model']::updateAll(['$set' => ['qrcode' => $qrcodeItem]], ['_id' => new MongoId($args['associatedId'])])) {
            LogUtil::error(['message' => 'Fail to update qrcode info', 'qrcodeItem' => var_export($qrcodeItem, true)], 'resque');
        }
        return true;
    }
}
