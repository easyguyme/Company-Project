<?php
namespace backend\components\wechat;

use backend\exceptions\ApiException;
use backend\utils\StringUtil;
use backend\utils\LogUtil;
use yii\base\Component;
use Yii;

class JsSDK extends Component
{
    /**
     * wechat js sdk app id
     * @var string
     */
    public $appId;
    /**
     * wechat js sdk app secret
     * @var string
     */
    public $appSecret;
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
    const PREFIX = 'jssdk_';

    const CACHE_TYPE_TICKET = 'ticket_';
    const CACHE_TYPE_TOKEN = 'token_';

    /**
     * get sign package
     * @return array
     */
    public function getSignPackage($channelId = null)
    {
        /*
        $channelId = '54d9c155e4b0abe717853ee1';
        if (!empty($channelId)) {
            $sign = Yii::$app->wechatSdk->getSignPackage($channelId);
            return $sign;
        }
        */

        // 注意 URL 一定要动态获取，不能 hardcode.
        $url = $this->refererUrl;
        if (empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->refererUrl = $url;
        }

        $jsapiTicket = $this->_getJsApiTicket();
        LogUtil::error(['message' => 'get jsapiTicket with old method','jsapiTicket' => $jsapiTicket], 'weixin');
        $timestamp = time();
        $nonceStr = StringUtil::rndString(16, StringUtil::ALL_DIGITS_LETTERS);

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
            "url" => $url,
        );
        LogUtil::error(['message' => 'get signPackage with old method','signPackage' => json_encode($signPackage)], 'weixin');
        return $signPackage;
    }

    /**
     * get js api ticket
     * @return string
     */
    private function _getJsApiTicket()
    {
        // get jsapi_ticket from cache
        $ticket = Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_TICKET . $this->appId);
        if (empty($ticket)) {
            // get access token
            $accessToken = $this->_getAccessToken();
            $url = $this->domain . "cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode(Yii::$app->curl->setOption(CURLOPT_REFERER, $this->refererUrl)->get($url));
            //object(stdClass)#54 (2) { ["errcode"]=> int(42001) ["errmsg"]=> string(20) "access_token expired"}

            if (!empty($res->ticket) && isset($res->expires_in)) {
                $ticket = $res->ticket;
                // set jsapi_ticket into cache
                Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_TICKET . $this->appId, $ticket, $res->expires_in);
            } else {
                throw new ApiException(404, 'No ticket.');
            }
        }

        return $ticket;
    }

    private function _getAccessToken()
    {
        // get token from cache
        $access_token = Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_TOKEN . $this->appId);
        if (empty($access_token)) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = $this->domain . "cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode(Yii::$app->curl->get($url));
            if (!empty($res->access_token) && isset($res->expires_in)) {
                $access_token = $res->access_token;
                // set jsapi_ticket into cache
                Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_TOKEN . $this->appId, $access_token, $res->expires_in);
            } else {
                throw new ApiException(404, 'No access token.');
            }
        }
        return $access_token;
    }

    private function getAppId($channelId)
    {
        $appId = Yii::$app->cache->get(self::PREFIX . self::CACHE_TYPE_APPID . $channelId);
        if (empty($appId)) {
            $account = Yii::$app->weConnect->getAccount($channelId);
            $appId = $account['appId'];
            Yii::$app->cache->set(self::PREFIX . self::CACHE_TYPE_APPID . $channelId, $appId);
        }
        return $appId;
    }
}
