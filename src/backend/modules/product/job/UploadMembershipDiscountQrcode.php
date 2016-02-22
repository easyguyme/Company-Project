<?php
namespace backend\modules\product\job;

use Yii;
use MongoId;
use backend\utils\LogUtil;
use backend\modules\product\models\MembershipDiscount;

class UploadMembershipDiscountQrcode
{
    public function perform()
    {
        $args = $this->job->payload['args'];
        $args = json_decode($args, true);
        if (empty($args['qrcodeType']) || empty($args['accountId']) || empty($args['associatedId']) || empty($args['membershipDiscountId'])) {
            LogUtil::error(['message' => 'Missing params', 'params' => $args], 'upload_resque');
            return false;
        }

        $qrcode = Yii::$app->qrcode->create(rtrim(DOMAIN, '/'), $args['qrcodeType'], $args['associatedId'], new MongoId($args['accountId']));

        //update membership discount qrcode info
        $qrcodeItem = [
            '_id' => $qrcode->_id,
            'qiniuKey' => $qrcode->qiniuKey,
        ];
        if (false == MembershipDiscount::updateAll(['$set' => ['qrcode' => $qrcodeItem]], ['_id' => new MongoId($args['membershipDiscountId'])])) {
            LogUtil::error(['message' => 'Fail to update MembershipDiscount qrcode info', 'qrcodeItem' => var_export($qrcodeItem, true), 'membershipDiscountId' => $args['membershipDiscountId']], 'upload_resque');
        }
        return true;
    }
}
