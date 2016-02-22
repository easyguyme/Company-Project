<?php
namespace backend\components\wechat;

use backend\exceptions\ApiException;
use backend\utils\StringUtil;
use yii\base\Component;
use Yii;
use backend\utils\LogUtil;

class WechatSdk extends Component
{
    /**
     * wechat js sdk domain
     * @var string
     */
    public $domain;

    /**
     * referer domain is the project domain
     * @var string
     */
    public $refererDomain;

    /**
     * the url used wechat js sdk
     * @var string
     */
    public $refererUrl;
    /**
     * jssdk cache prefix
     */

    public $channelId;

    const PREFIX = 'jssdk_';

    const CACHE_TYPE_TICKET = 'ticket_';
    const CACHE_TYPE_TOKEN = 'token_';
    const CACHE_TYPE_APPID = 'appid_';

    private function getAppId($channelId)
    {
        $appId = Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_APPID . $channelId);
        if (empty($appId)) {
            $appId = '';
            try {
                $account = Yii::$app->weConnect->getAccount($channelId);
                $appId = $account['appId'];
            } catch (yii\base\Exception   $e) {
                LogUtil::error(['message' => 'Get Account occurs a error', 'error' => $e->getMessage()], 'weixin');
            }
            Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_APPID . $channelId, $appId);
        }
        LogUtil::info(['message' => 'get appId','appId' => $appId], 'weixin');
        return $appId;
    }

    private function getTicket($channelId)
    {
        $ticket = Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_TICKET . $channelId);
        if (empty($ticket)) {
            $accessToken = $this->getAccessToken($channelId);
            $url = $this->domain . "cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $ticket = '';
            try {
                $res = json_decode(Yii::$app->curl->setOption(CURLOPT_REFERER, $this->refererUrl)->get($url));
                if (!empty($res->ticket) && isset($res->expires_in)) {
                    $ticket = $res->ticket;
                    Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_TICKET . $channelId, $ticket, $res->expires_in);
                }
            } catch (yii\base\Exception $e) {
                LogUtil::error(['message' => 'Get ticket occurs a error', 'error' => $e->getMessage()], 'weixin');
            }
        }
        LogUtil::info(['message' => 'get ticket','ticket' => $ticket], 'weixin');
        return $ticket;
    }

    private function getAccessToken($channelId)
    {
        try {
            $token =  Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_TOKEN . $channelId);
            if (empty($token)) {
                $data = Yii::$app->weConnect->getAccessToken($channelId);
                $token = $data['token'];
                $expireDateTime = $data['expireDateTime'];
                $expire_in = $expireDateTime / 1000 - time();
                if ($expire_in > 0) {
                    Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_TOKEN . $channelId, $token, $expire_in);
                }
            }
            LogUtil::info(['message' => 'get token','token' => $token], 'weixin');
            return $token;
        } catch (yii\base\Exception $e) {
            LogUtil::error(['message' => 'Get access_token occurs error', 'error' => $e->getMessage()], 'weixin');
            return '';
        }
    }

    /**
     * Get Wx AccessToken
     * @param  string $channelId The wx account id
     * @return string            AccessToken
     */
    public function getWxAccessToken($channelId = null)
    {
        if (empty($channelId)) {
            $channelId = $this->channelId;
        }
        $accessToken = $this->getAccessToken($channelId);
        return $accessToken;
    }

    /**
     * get sign package
     * @return array
     */
    public function getSignPackage($channelId = null)
    {
        if (empty($channelId)) {
            $channelId = $this->channelId;
        }
        $jsapiTicket = $this->getTicket($channelId);
        $appId = $this->getAppId($channelId);
        // 注意 URL 一定要动态获取，不能 hardcode.
        if (empty($this->refererUrl)) {
            $url = $this->refererDomain . substr(Yii::$app->request->getUrl(), 1);
        } else {
            $url = $this->refererUrl;
        }
        LogUtil::info(['message' => 'get url', 'url' => $url], 'weixin');
        $timestamp = time();
        $nonceStr = StringUtil::rndString(16, StringUtil::ALL_DIGITS_LETTERS);
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
        );
        LogUtil::info(['message' => 'get signPackage','signPackage' => json_encode($signPackage)], 'weixin');
        return $signPackage;
    }
}
