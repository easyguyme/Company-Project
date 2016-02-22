<?php
namespace backend\modules\member\utils;

use Yii;

class Conf
{
    public static function getContentInfo($channelId, $accountId)
    {
        $channel = Yii::$app->weConnect->getAccounts([$channelId]);
        $appId = isset($channel[0]['appId']) ? $channel[0]['appId'] : '';
        return ['appId' => $appId, 'channelId' => $channelId];
    }

    public static function pickNews($channelId, $accountId)
    {
        $result = 'others';
        $channel = Yii::$app->weConnect->getAccounts([$channelId]);
        $channelType = isset($channel[0]['channel']) ? $channel[0]['channel'] : '';
        $accountType = isset($channel[0]['accountType']) ? $channel[0]['accountType'] : '';
        if ($channelType === 'WEIXIN' &&  in_array($accountType, ['SUBSCRIPTION_ACCOUNT', 'SUBSCRIPTION_AUTH_ACCOUNT'])) {
            $result = 'subscription';
        }
        return $result;
    }

    public static function getChannelId($channelId, $accountId)
    {
        return ['channelId' => $channelId];
    }
}
