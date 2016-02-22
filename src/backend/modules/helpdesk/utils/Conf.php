<?php
namespace backend\modules\helpdesk\utils;

use Yii;
use backend\modules\helpdesk\models\HelpDeskSetting;

class Conf
{
    public static function isHelpDeskSet($channelId, $accountId)
    {
        $accountId = new \MongoId($accountId);
        $setting = HelpDeskSetting::findOne(['accountId' => $accountId, 'channels.id' => $channelId]);
        if (!empty($setting)) {
            return true;
        }
        return false;
    }

    public static function getContentInfo($channelId, $accountId)
    {
        $channel = Yii::$app->weConnect->getAccounts([$channelId]);
        $appId = isset($channel[0]['appId']) ? $channel[0]['appId'] : '';
        return ['appId' => $appId, 'channelId' => $channelId];
    }
}
