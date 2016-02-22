<?php
namespace backend\components;

use backend\utils\StringUtil;
use Yii;
use yii\base\Component;
use yii\helpers\Json;

/**
 * Url service used to handle the url.
 * @author Harry Sun
 */
class UrlService extends Component
{
    /**
     * The short url domain
     * @var string
     */
    public $shortUrlDomain;

    /**
     * The url is used to shorten a url
     * @var string
     */
    private $shortenUrl;

    /**
     * The url is used to get a short url statistics
     * @var string
     */
    private $statisticsUrl;

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
        parent::init();
        $this->shortenUrl = $this->shortUrlDomain . '/s/';
        $this->statisticsUrl = $this->shortUrlDomain . '/i';
    }

    /**
     * Shoreten a url
     * @param  string $longUrl the long url
     * @return array|null      the short url array
     *
     * The short url object should be like the following:
     * ~~~
     * {
     *   "Clicks":"0",
     *   "Long":"https://community.emc.com/thread/197851?start=0\u0026tstart=0",
     *   "Short":"http://gourl.im/BhTR",
     *   "Key":"BhTR"
     * }
     * ~~~
     */
    public function shortenUrl($longUrl)
    {
        if (empty($longUrl)) {
            throw new Exception("Missing url");
        }
        if (!$this->isUrl($longUrl)) {
            throw new Exception("$longUrl is not a url");
        }
        $url = $this->shortenUrl . '?url=' . urlencode($longUrl);
        $shortStr = Yii::$app->curl->post($url);
        $shortObj = StringUtil::isJson($shortStr) ? Json::decode($shortStr, true) : null;
        return $shortObj;
    }

    /**
     * Get a short url statistics
     * @param string $shortUrl a short url or key
     * @return array|null      the short url statistics object
     *
     *
     * If use $shortUrl like the following:
     *
     * ~~~
     * http://gourl.im/i/BhTR?from=20150101&to=20150115
     * ~~~
     *
     * or
     *
     * ~~~
     * BhTR?from=20150101&to=20150115
     * ~~~
     *
     * The short url statistics object should be like the following:
     *
     * ~~~
     * {
     *    "Clicks": 2,
     *    "Daily": [
     *        {
     *            "Key": "BhTq",
     *            "Day": "20150117",
     *            "TotalClicks": 2,
     *            "Hours": {
     *                "1": 0,
     *                "2": 0,
     *                "3": 0,
     *                "4": 0,
     *                "5": 0,
     *                "6": 0,
     *                "7": 0,
     *                "8": 0,
     *                "9": 0,
     *                "10": 0,
     *                "11": 0,
     *                "12": 0,
     *                "13": 0,
     *                "14": 0,
     *                "15": 0,
     *                "16": 2,
     *                "17": 0,
     *                "18": 0,
     *                "19": 0,
     *                "20": 0,
     *                "21": 0,
     *                "22": 0,
     *                "23": 0,
     *                "24": 0
     *            }
     *        }
     *    ],
     *    "Key": "BhTq",
     *    "Long": "http://staging.cp.augmarketing.cn/site/login",
     *    "Short": "http://u.augmarketing.cn/BhTq"
     * }
     * ~~~
     *
     * If use $shortUrl without form and to params like the following:
     *
     * ~~~
     * http://gourl.im/i/BhTR
     * ~~~
     *
     * or
     *
     * ~~~
     * BhTR
     * ~~~
     *
     * The short url statistics object should be like the following:
     *
     * ~~~
     * {
     *    "Clicks": 2,
     *    "Key": "BhTq",
     *    "Long": "http://staging.cp.augmarketing.cn/site/login",
     *    "Short": "http://u.augmarketing.cn/BhTq"
     * }
     * ~~~
     *
     */
    public function statistics($shortUrl)
    {
        if (!$this->isUrl($shortUrl)) {
            $shortUrl = $this->shortenKey2Url($shortUrl);
        }
        $shortUrl = str_replace($this->shortUrlDomain, $this->statisticsUrl, $shortUrl);
        $statisticsStr = Yii::$app->curl->get($shortUrl);
        $statisticsObj = StringUtil::isJson($statisticsStr) ? Json::decode($statisticsStr, true) : null;
        return $statisticsObj;
    }

    /**
     * Covent a short url key to short url
     * @param  string $key
     * @return string      the short url
     */
    public function shortenKey2Url($key)
    {
        return $this->shortUrlDomain . '/' . $key;
    }

    /**
     * Covent a short url to short url key
     * @param  string $shortUrl
     * @return string      the short url key
     */
    public function shortenUrl2Key($shortUrl)
    {
        return str_replace($this->shortUrlDomain . '/', '', $shortUrl);
    }

    /**
     * Judge a string whether a url
     * @param  string   $url
     * @return boolean
     */
    public function isUrl($url)
    {
        return preg_match("/^http(s)?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $url);
    }
}
