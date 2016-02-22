<?php
namespace backend\components;

use Yii;
use yii\web\Cookie;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use MongoId;
use backend\models\Token;
use backend\models\User;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\exceptions\InvalidParameterException;
use backend\utils\LogUtil;
use yii\helpers\ArrayHelper;

/**
 * This is a base trait for controller
 * @author Harry Sun
 */
trait BaseControllerTrait
{
    public function getParams($key = null, $default = null)
    {
        $rawBody = \Yii::$app->request->getRawBody();
        if (!StringUtil::isJson($rawBody)) {
            LogUtil::error(['url' => Yii::$app->request->absoluteUrl, 'header' => ArrayHelper::toArray(Yii::$app->request->headers), 'rawbody' => $rawBody]);
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }
        $rawArr = Json::decode($rawBody, true);
        if (!empty($key)) {
            if (isset($rawArr[$key])) {
                return $rawArr[$key];
            }

            return $default;
        }

        return $rawArr;
    }

    public function getQuery($key = null, $default = null)
    {
        return \Yii::$app->request->get($key, $default);
    }

    /**
     * Get the channel ID of WeConnect.
     * As channel ID is a required field to integrate with WeConnect,
     * exception will be thrown if no channel ID is not got.
     * @throws BadRequestHttpException If channelId can not be got from request parameters or payload
     * @return string channelId the channel ID of WeConnect
     */

    public function getChannelId()
    {
        if (\Yii::$app->request->isGet) {
            $channelId = $this->getQuery('channelId');
        } else {
            $channelId = $this->getParams('channelId');
        }

        if (!$channelId) {
            throw new BadRequestHttpException('Missing channel id');
        }

        return $channelId;
    }

    public function getUserId()
    {
        $token = $this->getAccessToken();
        $tokenInfo = Token::getToken($token);
        return $tokenInfo->userId;
    }

    public function getUser()
    {
        $userId = $this->getUserId();
        return User::findOne(['_id' => $userId]);
    }

    /**
     * Get the account id according to the accessToken
     * @return MongoId | boolean, the PK for the account or false for no such account found
     */
    public function getAccountId()
    {
        $accountId = $this->getAccountIdFromCookies();
        if (!empty($accountId)) {
            return new MongoId($accountId);
        }

        $token = $this->getAccessToken();
        $tokenInfo = Token::getToken($token);

        if (empty($tokenInfo)) {
            return false;
        }

        return $tokenInfo->accountId;
    }

    /**
     * Set accesstoken
     * @return string
     * @author Rex Chen
     */
    public function setAccessToken($accessToken)
    {
        $cookies = Yii::$app->response->cookies;
        $cookies->add(new Cookie(['name' => 'accesstoken', 'value' => $accessToken, 'expire' => time() + Token::EXPIRE_TIME]));
    }

    /**
     * Update accesstoken cookies expire
     * @return string
     * @author Rex Chen
     */
    public function updateAccessTokenExpire()
    {
        $cookies = Yii::$app->request->cookies;
        if (($cookie = $cookies->get('accesstoken')) !== null) {
            $cookie->expire = time() + Token::EXPIRE_TIME;
            Yii::$app->response->cookies->add($cookie);
        }
    }

    /**
     * Get accesstoken from cookies
     * @return string
     * @author Rex Chen
     */
    public function getAccessToken()
    {
        $cookies = Yii::$app->request->cookies;
        $token = '';
        if (($cookie = $cookies->get('accesstoken')) !== null) {
            $token = $cookie->value;
        }
        return $token;
    }

    /**
     * Get accountId from cookies
     * @return string
     */
    private function getAccountIdFromCookies()
    {
        $cookies = Yii::$app->request->cookies;
        $accountId = '';
        if (($cookie = $cookies->get('accountId')) !== null) {
            $accountId = $cookie->value;
        }
        return $accountId;
    }

    /**
     * Get the timezone offset from request
     * @return integer, the offset between UTC and browser
     */
    public function getTimezoneOffset()
    {
        $timezoneOffset = $this->getQuery('tmoffset');
        return intval($timezoneOffset);
    }

    private function _getMatchDateItem($data, $targetTimestamp)
    {
        $matchItem = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                if ($item['refDate'] == $targetTimestamp * 1000) {
                    $matchItem = $item;
                }
            }
        }

        return $matchItem;
    }

    public function formateResponseData($data, $keyMap, $startDate, $endDate)
    {
        $destResult = ['statDate' => []];
        if (!empty($keyMap)) {
            foreach ($keyMap as $resultKey => $sourceKey) {
                $destResult[$resultKey] = [];
            }
        }

        if (!empty($data)) {
            $startTimestamp = intval(TimeUtil::ms2sTime($startDate));
            $endTimestamp = intval(TimeUtil::ms2sTime($endDate));
            for ($recur = $startTimestamp; $recur <= $endTimestamp; $recur += 60 * 60 * 24) {
                if (!empty($targetItem = $this->_getMatchDateItem($data, $recur))) {
                    // timezone to add 8 hours
                    $dateString = date("Y-m-d", $recur + 60 * 60 * 8);
                    $destResult['statDate'][] = $dateString;
                    foreach ($keyMap as $resultKey => $sourceKey) {
                        $destResult[$resultKey][] = $targetItem[$sourceKey];
                    }
                }
            }
        }

        return $destResult;
    }

    /**
     * validate go api signature
     * @return boolean
     */
    private function validateSignature()
    {
        $request = Yii::$app->request;
        $body = $request->getIsGet() ? $this->getQuery() : $request->getRawBody();
        $headers = $request->getHeaders();

        if (is_string($body)) {
            $bodyStr = $body;
        } else {
            $bodyStr = ($body == []) ? 'null' : Json::encode($body, JSON_UNESCAPED_UNICODE);
        }
        $signature = hash_hmac('sha256', $bodyStr, 'Zc6smtltqrAToO44awoutxdS7LNsA81k');
        if (!empty($headers['X-API-Signature']) && $signature === $headers['X-API-Signature']) {
            return true;
        } else {
            LogUtil::error([
                'message' => 'module api signature error',
                'X-API-Signature' => empty($headers['X-API-Signature']) ? '' : $headers['X-API-Signature'],
                'bodyStr' => $bodyStr,
                'signature' => $signature
            ], 'module-api');
            return false;
        }
    }
}
