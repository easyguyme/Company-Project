<?php
namespace backend\modules\product\utils;

use Yii;

class Conf
{
    public static function getContentInfo($channelId, $accountId)
    {
        $channel = Yii::$app->weConnect->getAccounts([$channelId]);
        $appId = isset($channel[0]['appId']) ? $channel[0]['appId'] : '';
        return ['appId' => $appId, 'channelId' => $channelId];
    }
}
