<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use backend\exceptions\ApiDataException;
use backend\utils\LogUtil;
use backend\utils\UrlUtil;

class WeiboConnect extends Component
{
    public $appKey;
    public $appSecret;
    public $redirectUri;
    public $sinaOauthDomain;

    public function init()
    {
        //TODO
    }

    /**
     * Get the Oauth2/authorize url
     * @return array ['bindPath'=>'https://api.weibo.com/oauth2/authorize?client_id=123050457758183&redirect_uri=http://www.example.com/response&response_type=code']
     * @author Hank Liu
     **/
    public function getAuthorizeCodePath($accessToken)
    {
        $sinaOauthDomain = $this->sinaOauthDomain;
        $clientId = $this->appKey;
        $redirectUri = UrlUtil::getDomain() . $this->redirectUri;
        $redirectUri = urlencode($redirectUri);

        $path = "$sinaOauthDomain/authorize?client_id=$clientId&redirect_uri=$redirectUri&response_type=code&forcelogin=true";
        $bindPath = ['bindPath' => $path];
        return $bindPath;
    }

    /**
     * Get the OAuth2/access_token
     * @param string $code authorization code
     * @return access_token array
     *  example :
     *  {
     *      "access_token": "2.00uKK8BDUM6soDff27cd1e80pJaV3B",
     *      "expires_in": 1234,
     *      "remind_in":"798114",
     *      "uid":"12341234"
     *  }
     * @author Hank Liu
     **/
    public function getAccessToken($code)
    {
        $sinaOauthDomain = $this->sinaOauthDomain;
        $clientId = $this->appKey;
        $clientSecret = $this->appSecret;
        $redirectUri = UrlUtil::getDomain() . $this->redirectUri;
        $url = "$sinaOauthDomain/access_token?client_id=$clientId&client_secret=$clientSecret&grant_type=authorization_code&code=$code&redirect_uri=$redirectUri";
        return $this->_post($url, 'channel', 'uid');
    }

    /**
     * Get the oauth2/get_token_info
     * @param string $token bind weibo access token
     * @return token infos array
     *  example :
     *      {"uid":2769648804,"appkey":"3500181882","scope":null,"create_at":1422335002,"expire_in":654982}
     * @author Hank Liu
     **/
    public function getBindWeiboUUID($token)
    {
        $sinaOauthDomain = $this->sinaOauthDomain;
        $url = "$sinaOauthDomain/get_token_info?access_token=$token";
        return $this->_post($url, 'channel', 'uid', [], true);
    }

    /**
     * Get the oauth2/get_token_info
     * @param string $token bind weibo access token
     * @return token infos array
     *  example :
     *      {
     *          "id": 2769648804,
     *          "idstr": "2769648804",
     *          "class": 1,
     *          "screen_name": "kraskal",
     *          "name": "kraskal",
     *          "province": "43",
     *          "city": "1",
     *          "location": "湖南 长沙",
     *          "description": "改变可以改变的自己",
     *          "url": "",
     *          "profile_image_url": "http://tp1.sinaimg.cn/2769648804/50/5700695078/1",
     *          "profile_url": "u/2769648804",
     *          "domain": "",
     *          "weihao": "",
     *          "gender": "m",
     *          "followers_count": 32,
     *          "friends_count": 161,
     *          "pagefriends_count": 0,
     *          "statuses_count": 30,
     *          "favourites_count": 0,
     *          "created_at": "Fri Feb 01 11:02:35 +0800 2013",
     *          "following": false,
     *          "allow_all_act_msg": false,
     *          "geo_enabled": true,
     *          "verified": false,
     *          "verified_type": -1,
     *          "remark": "",
     *          ....
     *      }
     * @author Hank Liu
     **/
    public function getBindWeiboInfo($token, $uid)
    {
        $source = $this->appKey;
        $url = "https://api.weibo.com/2/users/show.json?source=$source&access_token=$token&uid=$uid";
        return $this->_get($url, 'channel', 'id');
    }

    /**
     * account/end_session: logout
     * @param string $token bind weibo access token
     * @return token infos array
     *  example :
     *      {
     *          "id": 2769648804,
     *          "idstr": "2769648804",
     *          "class": 1,
     *          "screen_name": "kraskal",
     *          "name": "kraskal",
     *          "province": "43",
     *          "city": "1",
     *          "location": "湖南 长沙",
     *          "description": "改变可以改变的自己",
     *          "url": "",
     *          "profile_image_url": "http://tp1.sinaimg.cn/2769648804/50/5700695078/1",
     *          "profile_url": "u/2769648804",
     *          "domain": "",
     *          "weihao": "",
     *          "gender": "m",
     *          "followers_count": 32,
     *          "friends_count": 161,
     *          "pagefriends_count": 0,
     *          "statuses_count": 30,
     *          "favourites_count": 0,
     *          "created_at": "Fri Feb 01 11:02:35 +0800 2013",
     *          "following": false,
     *          "allow_all_act_msg": false,
     *          "geo_enabled": true,
     *          "verified": false,
     *          "verified_type": -1,
     *          "remark": "",
     *          ....
     *      }
     * @author Hank Liu
     **/
    public function endSession($token)
    {
        $source = $this->appKey;
        $url = "https://api.weibo.com/2/account/end_session.json?source=$source&access_token=$token";
        return $this->_get($url, 'channel', 'id');
    }

    /**
     * oauth2/revokeoauth2: revoke weibo access token
     * @param string $token bind weibo access token
     * @return result of revoke weibo access token
     *  example :
     *      {"result":"true"}
     * @author Hank Liu
     **/
    public function revokeWeiboToken($token)
    {
        $url = "https://api.weibo.com/oauth2/revokeoauth2?access_token=$token";
        return $this->_get($url, 'channel', 'result');
    }

    /**
     * Using a get request to call weibo api
     * @param  string $url request url
     * @param  string $logTarget log file directory
     * @param  string $resultField the flag of the success request
     * @param  string $params request parameters
     * @throws ApiDataException
     */
    private function _get($url, $logTarget, $resultField, $params = [])
    {
        $resultJson = null;

        if (count($params) == 0) {
            $resultJson = Yii::$app->curl->get($url);
        } else {
            $resultJson = Yii::$app->curl->get($url, $params);
        }

        LogUtil::info(['url' => 'GET ' . $url, 'params' => $params, 'response' => $resultJson], $logTarget);

        $result = json_decode($resultJson, true);

        if ($result && isset($result[$resultField])) {
            return $result;
        } else {
            throw new ApiDataException("GET " . $url, $resultJson);
        }
    }

    /**
     * Using a post request to call weibo api
     * @param  string $url request url
     * @param  string $logTarget log file directory
     * @param  string $resultField the flag of the success request
     * @param  string $params request parameters
     * @param  boolean $returnResultData
     * @throws ApiDataException
     */
    private function _post($url, $logTarget, $resultField, $params = [], $returnResultData = true)
    {
        $resultJson = null;

        if (count($params) == 0) {
            $resultJson = Yii::$app->curl->post($url);
        } else {
            $resultJson = Yii::$app->curl->postJson($url, $params);
        }

        LogUtil::info(['url' => 'POST ' . $url, 'params' => $params, 'response' => $resultJson], $logTarget);

        $result = json_decode($resultJson, true);

        if ($result && isset($result[$resultField])) {
            if ($returnResultData) {
                return $result;
            } else {
                return true;
            }
        } else {
            throw new ApiDataException("POST " . $url, $resultJson);
        }
    }
}
