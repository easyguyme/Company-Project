<?php
namespace backend\utils;

use Yii;
use yii\helpers\Json;
use backend\exceptions\ApiDataException;
use backend\models\ServiceSetting;

class MessageUtil
{
    /**
     * Send mobile message.
     * @param string $mobile The phone number
     * @param string $text, The message content.
     * @param mixed $accountId, Account mongo ID or account ID string.
     * @return Array
     */
    public static function sendMobileMessage($mobile, $text, $accountId)
    {
        if (is_string($accountId)) {
            $accountId = new \MongoId($accountId);
        }
        $url = YUNPIAN_DOMAIN;
        $apiKey = YUNPIAN_API_KEY;
        $setting = ServiceSetting::findByAccountId($accountId);
        if (!empty($setting) && !empty($setting->message)) {
            $url = $setting->message['url'];
            $apiKey = $setting->message['apiKey'];
        }
        $params = [
            'apikey' => $apiKey,
            'mobile' => $mobile,
            'text' => $text
        ];

        Yii::$app->curl->setHeaders(['Content-Type: application/x-www-form-urlencoded']);
        $resultJson = Yii::$app->curl->post($url, http_build_query($params));

        LogUtil::info(['url' => $url, 'params' => $params, 'response' => $resultJson], 'mobile-message');
        $result = Json::decode($resultJson, true);

        if ($result && isset($result['code']) && 0 == $result['code'] && isset($result['result'])
            && isset($result['result']['count']) && $result['result']['count'] > 0) {
            self::recoreMessageCount('omni_record_message_total');
            return true;
        } else {
            LogUtil::error(['url' => $url, 'params' => $params, 'response' => $resultJson], 'mobile-message');
            return false;
        }
    }

    public static function recoreMessageCount($type)
    {
        $redis = Yii::$app->cache->redis;
        $redis->incr($type);
    }
}
