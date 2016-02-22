<?php
namespace backend\behaviors;

use Yii;
use yii\base\Behavior;
use backend\models\Store;
use backend\models\Staff;
use backend\models\Channel;
use backend\exceptions\InvalidParameterException;

class QrcodeBehavior extends Behavior
{
    public function deleteQrcode($channelId, $qrcodeId)
    {
        //can not delete staff qrcode,if the qrcode is deleted,i can not update the template of qrcode when staff update his username
        $staffInfo = Staff::findOne(['qrcodeId' => $qrcodeId]);
        if (!empty($staffInfo)) {
            throw new InvalidParameterException(Yii::t('channel', 'delete_staff_qrcode_fail'));
        }
        //delete store qrcode
        Store::deleteStoreQrcode($channelId, $qrcodeId);
        //when delete subscribe qrcode, we need set qrcode to '' in channel collection
        Channel::updateAll(['$set' => ['qrcodeId' => '']], ['channelId' => $channelId, 'qrcodeId' => $qrcodeId]);
    }
}
