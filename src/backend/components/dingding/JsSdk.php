<?php
namespace backend\components\dingding;

use Yii;
use yii\base\Component;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;

class JsSdk extends Component
{
    public function getConfig($suiteKey, $corpId, $appId, $url)
    {
        $token = $this->getToken($suiteKey, $corpId, $appId);
        $ticket = $this->getTicket($token['accesstoken']);
        $time = time();
        $noncestr = StringUtil::rndString();
        $sig = $this->generateSig($ticket, $noncestr, $time, $url);
        return [
            'agentId' => $token['agentId'],
            'corpId' => $corpId,
            'timeStamp' => $time,
            'nonceStr' => $noncestr,
            'signature' => $sig,
        ];
    }

    public function getToken($suiteKey, $corpId, $appId)
    {
        $cache = Yii::$app->cache;
        $key = $suiteKey . '_' . $corpId . '_' . $appId;
        $token = $cache->get($key);
        if (empty($token)) {
            $result = Yii::$app->weConnect->getDDCorpToken($suiteKey, $corpId, $appId);
            //get accesstoken and cache it
            $token = [
                'agentId' => $result['appInfo']['agentId'],
                'accesstoken' => $result['token'],
                'accountId' => $result['appInfo']['accountId']
            ];
            $expire = TimeUtil::ms2sTime($result['expireDateTime']) - time();
            $cache->set($key, $token, $expire);
        }
        return $token;
    }

    private function getTicket($accesstoken)
    {
        $cache = Yii::$app->cache;
        $ticket = $cache->get($accesstoken);
        if (empty($ticket)) {
            $result = Yii::$app->ddConnect->getJsTicket($accesstoken);
            $ticket = $result['ticket'];
            $cache->set($accesstoken, $ticket, $result['expires_in']);
        }

        return $ticket;
    }

    private function generateSig($ticket, $noncestr, $time, $url)
    {
        $plain = 'jsapi_ticket=' . $ticket .
            '&noncestr=' . $noncestr .
            '&timestamp=' . $time .
            '&url=' . $url;
        return sha1($plain);
    }
}
