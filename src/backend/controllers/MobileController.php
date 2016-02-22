<?php
namespace backend\controllers;

use Yii;
use MongoDate;
use MongoId;
use backend\modules\product\models\Coupon;
use backend\modules\product\models\MembershipDiscount;
use backend\modules\product\models\CouponLog;
use backend\utils\MessageUtil;
use backend\utils\StringUtil;
use yii\web\BadRequestHttpException;
use backend\components\Controller;
use backend\modules\member\models\Member;
use backend\models\Token;
use yii\web\ServerErrorHttpException;
use backend\models\Account;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\MemberProperty;
use backend\models\Captcha;
use backend\utils\LogUtil;
use yii\helpers\Json;
use backend\modules\member\models\ScoreRule;
use backend\exceptions\InvalidParameterException;
use backend\models\User;
use backend\utils\TimeUtil;
use backend\utils\LanguageUtil;
use backend\exceptions\ApiDataException;
use backend\behaviors\CaptchaBehavior;
use backend\models\Qrcode;
use backend\models\Channel;
use backend\utils\BrowserUtil;
use backend\models\Store;
use backend\modules\game\models\Game;
use yii\helpers\ArrayHelper;
use backend\utils\MongodbUtil;
use backend\behaviors\MemberBehavior;
use backend\utils\UrlUtil;
use backend\models\Follower;

class MobileController extends Controller
{
    //const for default company name
    const DEFAULT_COMPANY = '群脉CRM';

    //member bind redirect to third party
    const TYPE_REDIRECT = 1;
    //member bind redirect to omnisocials
    const TYPE_REDIRECT_INSIDE = 2;

    const CAPTCHA_TYPE_BIND = 'bind';
    const CAPTCHA_TYPE_SIGNUP = 'signup';
    const CAPTCHA_TYPE_COMPANY_INFO = 'updateCompanyInfo';
    const CAPTCHA_TYPE_EXCHANGE = 'exchange';

    //const for test bind
    const TEST_PHONE = '10010110110';
    const TEST_CODE = 'OMINIADMIN';

    //default alipay scopre
    const DEFAULT_ALIPAY_SCOPE = 'auth_userinfo';

    /**
     * Send mobile captcha.
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/mobile/send-captcha<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for send mobile captcha.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     mobile: string, phone number<br/>
     *     unionId: string<br/>
     *     language: 'zh_cn' or 'en_us', This param is just for update mobile<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     message: OK or Fail
     *     data: string, if success, It is verification code<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "message": "OK",
     *  "data": "456787"
     * }
     * </pre>
     */
    public function actionSendCaptcha()
    {
        $params = $this->getParams();
        if (empty($params['type']) || empty($params['mobile']) || empty($params['codeId']) || empty($params['code'])) {
            throw new BadRequestHttpException('Missing params');
        }
        $type = $params['type'];
        $mobile = $params['mobile'];
        if (in_array($type, [self::CAPTCHA_TYPE_COMPANY_INFO, self::CAPTCHA_TYPE_EXCHANGE])) {
            $params['accountId'] = $this->getAccountId();
        } else if (!in_array($type, [self::CAPTCHA_TYPE_BIND, self::CAPTCHA_TYPE_SIGNUP])) {
            throw new BadRequestHttpException('Invalid type');
        }

        $this->attachBehavior('CaptchaBehavior', new CaptchaBehavior);
        $companyInfo = $this->$type($params);
        $company = ($companyInfo['company'] === null) ? self::DEFAULT_COMPANY : $companyInfo['company'];
        $accountId = $companyInfo['accountId'];

        //limit captcha send by ip
        $ip = Yii::$app->request->userIp;
        $captcha = Captcha::getByIP($ip);

        $now = time();
        if (!empty($captcha)) {
            $sendTimeInt = MongodbUtil::MongoDate2TimeStamp($captcha->createdAt);
            $nextTime = $sendTimeInt + Yii::$app->params['captcha_send_interval'];
            if ($nextTime > $now) {
                throw new InvalidParameterException(['phone' => Yii::t('common', 'send_too_frequently')]);
            } else {
                $captcha->isExpired = true;
                $captcha->save();
            }
        }

        //get random string, length = 6, charlist = '0123456789'
        $code = StringUtil::rndString(6, 0, '0123456789');
        $text = str_replace('#code#', $code, Yii::$app->params['mobile_message_text']);
        $text = str_replace('#company#', $company, $text);

        $captcha = new Captcha();
        $captcha->ip = $ip;
        $captcha->code = $code;
        $captcha->mobile = $mobile;
        $captcha->isExpired = false;
        $captcha->accountId = $accountId;

        if (MessageUtil::sendMobileMessage($mobile, $text, $accountId) && $captcha->save()) {
            MessageUtil::recoreMessageCount('omni_record_message_' . $type);
            $result = ['message' => 'OK', 'data' => ''];
        } else {
            $result = ['message' => 'Error', 'data' => 'unknow error'];
        }

        return $result;
    }

    /**
     * Check bind.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/mobile/check-bind<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for check bind.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     redirect<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  redirect('http://dev.cp.augmarketing.cn/mobile/center?openId=3DoTAN2jmRmInqhC_CDLN7aSTzvfzo') or
     *  redirect('http://dev.cp.augmarketing.cn/mobile/center?memberId=549a73c3e9c2fb8d7c8b4569')
     * </pre>
     */
    public function actionCheckBind($type = '', $param = '')
    {
        $params = $this->getQuery();

        if (empty($params['state'])) {
            throw new BadRequestHttpException('missing params state');
        }

        $channelId = $params['state'];
        $channelInfo = Yii::$app->weConnect->getAccounts($channelId);
        if (empty($channelInfo[0]['channel'])) {
            throw new BadRequestHttpException('invalid channelId');
        }

        $userChannel = $channelInfo[0]['channel'];
        $redirect = !empty($type) ? base64_decode($param) : '';

        $mainDomain = UrlUtil::getDomain();

        if ($userChannel == Account::WECONNECT_CHANNEL_ALIPAY) {
            if (empty($params['auth_code'])) {
                throw new BadRequestHttpException('missing param auth_code');
            }
            LogUtil::info(['params' => $params, 'channelId' => $channelId, 'message' => 'alipay info'], 'channel');
            if ($params['scope'] == 'auth_userinfo') {
                $alipayUserInfo = Yii::$app->weConnect->getAlipayUserInfo($channelId, $params['auth_code']);
                $openId = $alipayUserInfo['originId'];
            } else {
                //call weconnect to get openId
                $openId = Yii::$app->weConnect->getAlipayOpenId($channelId, $params['auth_code']);
            }
        } else {
            if (empty($params['openId'])) {
                $openIdInfo = $this->getOriginAndOpenId($params);
                $origin = $openIdInfo['origin'];
                $openId = $openIdInfo['openId'];
            } else {
                $origin = Member::WECHAT;
                $openId = $params['openId'];
            }
        }

        //get member unionId from weconnect
        try {
            if (empty($alipayUserInfo)) {
                //to suport alipay,because alipay did not get followers again
                $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
            } else {
                $follower = $alipayUserInfo;
            }
        } catch (ApiDataException $e) {
            LogUtil::info(['WeConnect Exception' => 'Get follower info error', 'exception' => $e], 'channel');
            $follower = null;
        }

        //if follower not subscribed and (follower must subscribe before redirect or origin is weibo), redirect
        if ((empty($follower) || (isset($follower['subscribed']) && $follower['subscribed'] != true)) &&
             ($this->mustSubscribe($redirect) || $origin === Member::WEIBO)) {
            return $this->redirect($this->getSubscribePage($origin, $channelId, $type, $redirect));
        }

        //if the channel is alipay,we need to judge the member info whether exists,if the info is empty,wo call the first url
        if ($userChannel == Account::WECONNECT_CHANNEL_ALIPAY) {
            if (isset($follower['authorized']) && $follower['authorized'] == false) {
                if ($params['scope'] == 'auth_userinfo') {
                    LogUtil::info(['message' => 'weConnect authorized fail', 'follower' => $follower], 'channel');
                } else {
                    $redirectUrl = $mainDomain . '/api/mobile/check-bind';
                    if (!empty($type) && !empty($param)) {
                        $redirectUrl .= "/$type/$param";
                    }
                    $redirectUrl .= '?state=' . $channelId . '&appId=' . $params['appId'];
                    $redirectUrl = urlencode($redirectUrl);
                    $url = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?"
                              ."app_id=" . $params['appId'] . "&auth_skip=false&scope=auth_userinfo&redirect_uri=$redirectUrl";
                    LogUtil::info(['message' => 'can not get detailed follower info','url' => $url, 'follower' => $follower], 'channel');
                    return $this->redirect($url);
                }
            } else {
                LogUtil::info(['message' => 'authorized', 'follower' => $follower], 'channel');
            }
        }

        if (!empty($follower['unionId'])) {//unionId exists
            $unionId = $follower['unionId'];
            $member = Member::getByUnionid($unionId);
            if (empty($member)) {//no unionId but openId
                $member = Member::getByOpenId($openId);
                if (!empty($member)) {
                    $member->unionId = $unionId;
                    $member->save(true, ['unionId']);
                }
            }
        } else if (!empty($follower['originId'])) {
            $unionId = '';
            $member = Member::getByOpenId($openId);
        } else if (empty($follower) && !empty($params['appid']) && $userChannel == Account::WECONNECT_CHANNEL_WEIXIN) {
            LogUtil::info(['message' => 'Failed to get follower info', 'follower' => $follower, 'params' => $params]);
            $appId = $params['appid'];
            // not a follower, oAuth2.0 to get user_info
            $member = Member::getByOpenId($openId);
            if (empty($member)) {
                $component = Yii::$app->weConnect->getComponentToken();
                $componentAppId = $component['componentAppId'];
                $state = $channelId;
                $redirectUrl = UrlUtil::getDomain(). '/api/mobile/user-info';
                if (!empty($redirect)) {
                    $redirectUrl = $redirectUrl . '/' . $type . '/' . $param;
                }
                LogUtil::info(['message' => 'oauth2 user_info redirecturl', 'url' => $redirectUrl]);
                $redirectUrl = urlencode($redirectUrl);
                $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_userinfo&state=$state&component_appid=$componentAppId#wechat_redirect";
                return $this->redirect($url);
            } else {
                //member exists, will redirect to member center or 403
            }
        } else {
            LogUtil::error(['Mobile member' => 'Failed to get follower info']);
            return $this->redirect('/mobile/common/403');
        }

        LogUtil::info(['message' => 'Bind with follower', 'follower' => $follower], 'channel');

        if (empty($member)) {//if not exist redirect, get unionId to bind
            //urlencode to avoid lose $redirect query string when frontend get $redirect
            $redirect = urlencode($redirect);
            $redirectUrl = $mainDomain . '/mobile/member';
            if ($type == self::TYPE_REDIRECT) {
                $redirectUrl .= '/activate';
            } else {
                $redirectUrl .= '/center';
            }
            $redirectUrl .= "?openId=$openId&channelId=$channelId&unionId=$unionId&redirect=$redirect&redirectType=$type";
        } else {
            //if member is disabled, redirect to 403
            if ($member->isDisabled) {
                return $this->redirect('/mobile/common/403');
            }
            //if exist redirect to member center
            $social = [
                'channel' => $channelId,
                'openId' => $openId,
                'origin' => $origin,
                'originScene' => empty($follower['firstSubscribeSource']) ? '' : $follower['firstSubscribeSource']
            ];
            $this->addNewSocial($member, $social);

            $memberId = $member['_id'] . '';
            if ($type == self::TYPE_REDIRECT) {
                $str = (strpos($redirect, '?') !== false) ? '&' : '?';
                $redirectUrl = $redirect . $str . "quncrm_member=$memberId";
            } else {
                $accountId = new \MongoId($member['accountId']);
                $token = Token::createForMobile($accountId);
                if (empty($token['accessToken'])) {
                    throw new ServerErrorHttpException('Failed to create token for unknown reason.');
                }
                $accessToken = $token['accessToken'];
                $this->setAccessToken($accessToken);
                if ($type == self::TYPE_REDIRECT_INSIDE) {
                        $str = (strpos($redirect, '?') !== false) ? '&' : '?';
                        $redirectUrl = $redirect . $str . "memberId=$memberId&channelId=$channelId";
                } else {
                    $redirectUrl = $mainDomain . "/mobile/member/center?memberId=$memberId&channelId=$channelId";
                    if (!empty($member->cardExpiredAt) && $member->cardExpiredAt < TimeUtil::msTime()) {
                        $redirectUrl = $redirectUrl . '&cardExpired=1';
                    } else {
                        $redirectUrl = $redirectUrl . '&cardExpired=0';
                    }
                }
            }
        }

        return $this->redirect($redirectUrl);
    }

    private function addNewSocial($member, $social)
    {
        if ($member->socialAccountId == $social['channel']) {
            return;
        }

        $dbSocials = empty($member->socials) ? [] : $member->socials;
        $dbSocialChannels = [];
        foreach ($dbSocials as $dbSocial) {
            if (!empty($dbSocial['channel'])) {
                $dbSocialChannels[] = $dbSocial['channel'];
            }
        }
        if (!in_array($social['channel'], $dbSocialChannels)) {
            Member::updateAll(['$addToSet' => ['socials' => $social]], ['_id' => $member->_id]);
        }

        //remove follower
        Follower::removeByOpenId($member->accountId, $social['channel']);
    }

    /**
     * Redirect wexin to get code return openId or memberId
     */
    public function actionMember()
    {
        $channelId = $this->getQuery('channelId');
        $redirect = $this->getQuery('redirect');

        $baseUrl = UrlUtil::getDomain(). '/api/mobile/check-bind';
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT, $redirect);
        $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
        $this->redirect($url);
    }

    /**
     * OAuth action to bind member and redirect
     */
    public function actionMemberOauth()
    {
        $channelId = $this->getQuery('channelId');
        $redirect = $this->getQuery('redirect');
        if (empty($redirect)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $redirect = urlencode(UrlUtil::getDomain()) . $redirect;

        $baseUrl = UrlUtil::getDomain(). '/api/mobile/check-bind';
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $redirect);
        $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
        $this->redirect($url);
    }

    public function actionGame()
    {
        //check follower
        $channelId = $this->getQuery('channelId');
        $channel = Channel::getByChannelId($channelId);
        if (empty($channel) || !$this->checkBrowserIsRight($channel->origin)) {
            return $this->redirect('/mobile/common/remind?origin=' . $channel->origin);
        }

        $gameId = $this->getQuery('id');
        //suport old data
        if (empty($gameId)) {
            $gameId = $this->getQuery('gameId');
        }

        $mainDomain = UrlUtil::getDomain();
        $type = 'game_id_' . $gameId;
        $baseUrl = $mainDomain . '/api/mobile/check-follower?type='. $type;
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, '');
        $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
        return $this->redirect($url);
    }

    /**
     * This function is dealing with coupon
     * weibo only return a param except state,so all need param bind in a param called type use '_'
     */
    public function actionCoupon()
    {
        $channelId = $this->getQuery('channelId');
        $couponId = $this->getQuery('couponId');
        $type = $this->getQuery('type');

        $mainDomain = UrlUtil::getDomain();

        if ($type == 'received') {
            $number = $this->getQuery('number', 1);
            $baseUrl = $mainDomain . '/api/mobile/check-bind';
            $redirect = $mainDomain . '/api/product/membership-discount/received-coupon?couponId=' . $couponId . '&channelId=' . $channelId . '&number=' . $number;
            $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $redirect);
            $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
            return ['url' => $url];
        } else {
            $type = 'coupon_couponId_' . $couponId . '_channelId_' . $channelId;
            $baseUrl = $mainDomain . '/api/mobile/check-follower?type='. $type .'&couponId=' . $couponId . '&channelId=' . $channelId;
            $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, '');
            $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
            return $this->redirect($url);
        }
    }

    /**
     * get coupon detail info
     */
    public function actionOpenCoupon()
    {
        $couponId = $this->getQuery("couponId");
        $memberId = $this->getQuery("memberId");
        $isReceived = true;
        $message = '';

        if (empty($couponId)) {
            throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
        }

        $coupon = Coupon::findByPk(new MongoId($couponId));
        if (empty($coupon)) {
            throw new InvalidParameterException(Yii::t('product', 'membershipDiscount_is_deleted'));
        }

        // check expired
        $current = new MongoDate(strtotime(date('Y-m-d')));
        if ($coupon->time['type'] == Coupon::COUPON_ABSOLUTE_TIME && $coupon->time['endTime'] < $current) {
            $isReceived = false;
            $message = Yii::t('product', 'coupon_expired');
        }
        //check total
        if ($coupon->total <= 0) {
            $isReceived = false;
            $message = Yii::t('product', 'coupon_no_exists');
        }

        if (!empty($memberId)) {
            $where = ['couponId' => new MongoId($couponId), 'member.id' => new MongoId($memberId)];
            $couponNum = CouponLog::count($where);

            if ($couponNum >= $coupon->limit) {
                $isReceived = false;
                $message = Yii::t('product', 'coupon_is_received');
            }
        }
        return array_merge($coupon->toArray(), ['isReceived' => $isReceived, 'message' => $message]);
    }

    /**
     * get coupon about store from mobile
     * note: mobile only get some special modules
     */
    public function actionCouponStore()
    {
        $params = $this->getQuery();
        if (empty($params['couponId'])) {
            throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
        }

        $couponId = new MongoId($params['couponId']);
        $coupon = Coupon::findByPk($couponId);

        if (!empty($coupon)) {
            if ($coupon->storeType == Coupon::COUPON_ALL_STORE) {
                $stores = Store::find()->where(['accountId' => $coupon->accountId, 'isDeleted' => Coupon::NOT_DELETED])->orderBy(['_id' => SORT_DESC])->all();
                $coupon->stores = Coupon::conver2couponStore($stores);
            } else {
                if (!empty($coupon->stores)) {
                    foreach ($stores = $coupon->stores as &$store) {
                        $store['id'] = (string)$store['id'];
                    }
                    ArrayHelper::multisort($stores, 'id', SORT_DESC);
                    $coupon->stores = $stores;
                }
            }
        }
        return $coupon;
    }

    /**
     * check the user whether is follower
     */
    public function actionCheckFollower()
    {
        $params = $this->getQuery();
        $result = $this->checkSubscribe($params);
        if (is_string($result) && preg_match(StringUtil::URL_REGREX, $result)) {
            return $this->redirect($result);
        }
        unset($result);

        $mainDomain = UrlUtil::getDomain();
        $channelId = $params['state'];
        //this typs first param must is type,other params must be key_value
        $types = explode('_', $params['type']);
        $couponId = $params['couponId'] = $this->getTypeValue($types, 'couponId');
        $type = isset($types[0]) ? $types[0] : '';

        $redirecturl = '';
        switch ($type) {
            case 'game':
                $params['id'] = $this->getTypeValue($types, 'id');
                if (empty($params['id'])) {
                    throw new BadRequestHttpException('missing params gameId');
                }
                $game = Game::findByPk(new MongoId($params['id']));
                $baseUrl = $mainDomain . '/api/mobile/check-bind';
                if ($game->type == Game::TYPE_SHAKE) {
                    $game->url = $mainDomain . '/webapp/game/game/shake?id=' . $params['id'];
                }
                $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $game->url);
                $redirecturl = $this->buildRedirectUrl($channelId, $oauthRedirect);
                break;

            case 'coupon':
                $redirecturl = $mainDomain . '/mobile/product/coupon?couponId=' . $couponId . '&channelId=' . $channelId;
                break;
        }
        return $this->redirect($redirecturl);
    }

    /**
     * get the param value from types
     */
    private function getTypeValue($types, $key)
    {
        $index = array_search($key, $types);
        return isset($types[$index + 1]) ? $types[$index + 1] : '';
    }

    /**
     * check the use whether subscribe our channel,if not,redirct to subscribe page,otherwise return true
     */
    private function checkSubscribe($params)
    {
        if (empty($params['state'])) {
            throw new BadRequestHttpException('missing params state');
        }

        $channelId = $params['state'];
        $channelInfo = Yii::$app->weConnect->getAccounts($channelId);
        if (empty($channelInfo[0]['channel'])) {
            throw new BadRequestHttpException('invalid channelId');
        }

        $userChannel = $channelInfo[0]['channel'];

        if ($userChannel == Account::WECONNECT_CHANNEL_ALIPAY) {
            if (empty($params['auth_code'])) {
                throw new BadRequestHttpException('missing param auth_code');
            }
            LogUtil::info(['params' => $params, 'channelId' => $channelId, 'message' => 'alipay info'], 'channel');
            if ($params['scope'] == 'auth_userinfo') {
                $alipayUserInfo = Yii::$app->weConnect->getAlipayUserInfo($channelId, $params['auth_code']);
                $openId = $alipayUserInfo['originId'];
            } else {
                //call weconnect to get openId
                $openId = Yii::$app->weConnect->getAlipayOpenId($channelId, $params['auth_code']);
            }
        } else {
            if (empty($params['openId'])) {
                $openIdInfo = $this->getOriginAndOpenId($params);
                $origin = $openIdInfo['origin'];
                $openId = $openIdInfo['openId'];
            } else {
                $origin = Member::WECHAT;
                $openId = $params['openId'];
            }
        }

        //get member unionId from weconnect
        try {
            if (empty($alipayUserInfo)) {
                //to suport alipay,because alipay did not get followers again
                $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
            } else {
                $follower = $alipayUserInfo;
            }
        } catch (ApiDataException $e) {
            LogUtil::info(['WeConnect Exception' => 'Get follower info error', 'exception' => $e], 'channel');
            $follower = null;
        }

        if (empty($follower) || (isset($follower['subscribed']) && $follower['subscribed'] != true)) {
            //get qrcode url
            $qrcodeUrl = Qrcode::getAttentionQrcode($channelId);
            //wait for page url
            return UrlUtil::getDomain() . '/mobile/common/attention?imageUrl=' . $qrcodeUrl;
        } else {
            return $follower;
        }
    }

    public function actionMall()
    {
        $channelId = $this->getQuery('channelId');
        $goodsId = $this->getQuery('goodsId');
        $productId = $this->getQuery('productId');
        $mallAction = 'list';
        $urlParam = '';
        if (!empty($goodsId) && !empty($productId)) {
            $mallAction = 'detail';
            $urlParam = "?goodsId=$goodsId&productId=$productId";
        }

        $baseUrl = UrlUtil::getDomain(). '/api/mobile/check-bind';
        $redirect = UrlUtil::getDomain() . "/mobile/product/$mallAction$urlParam";
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $redirect);
        $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
        $this->redirect($url);
    }

    public function actionCampaign()
    {
        $channelId = $this->getQuery('channelId');
        $campaignId = $this->getQuery('campaignId');
        $campaignAction = $this->getQuery('action');

        $baseUrl = UrlUtil::getDomain(). '/api/mobile/check-bind';
        $redirect = UrlUtil::getDomain() . "/mobile/campaign/$campaignAction?campaignId=$campaignId&mustSubscribe=1";
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $redirect);
        $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
        $this->redirect($url);
    }

    public function actionFeedback()
    {
        $channelId = $this->getQuery('channelId');
        $mainDomain = UrlUtil::getDomain();
        $redirecturl = $mainDomain . '/mobile/feedback/add';
        $url = $mainDomain . '/api/mobile/base-oauth?channelId=' . $channelId . '&redirect=' . urlencode($redirecturl);
        $this->redirect($url);
    }

    /**
     * Base oauth action
     * @author Rex Chen
     */
    public function actionBaseOauth()
    {
        if (BrowserUtil::isWeixinBrowser() || BrowserUtil::isWeiboBrower() || BrowserUtil::isAliBrower()) {
            $channelId = $this->getQuery('channelId');
            $channel = Channel::getByChannelId($channelId);
            if (empty($channel) || !$this->checkBrowserIsRight($channel->origin)) {
                return $this->redirect('/mobile/common/404');
            }
            $redirect = $this->getQuery('redirect');
            $baseUrl = UrlUtil::getDomain() . '/api/mobile/openid';
            $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT_INSIDE, $redirect);
            $url = $this->buildRedirectUrl($channelId, $oauthRedirect);
            $this->redirect($url);
        } else {
            $this->redirect('/mobile/common/error');
        }
    }

    /**
     * Check browser is suit with origin
     */
    private function checkBrowserIsRight($origin)
    {
        switch ($origin) {
            case Channel::WECHAT:
                $isBrowerRight = BrowserUtil::isWeixinBrowser() ? true : false;
                break;
            case Channel::WEIBO:
                $isBrowerRight = BrowserUtil::isWeiboBrower() ? true : false;
                break;
            case Channel::ALIPAY:
                $isBrowerRight = BrowserUtil::isAliBrower() ? true : false;
                break;
            default:
                $isBrowerRight = false;
                break;
        }
        return $isBrowerRight;
    }

    public function actionOpenid($type = '', $param = '')
    {
        $params = $this->getQuery();
        $channelId = $params['state'];

        if (!empty($params['auth_code'])) {
            //to suport alipay
            $origin = Member::ALIPAY;
            $openId = Yii::$app->weConnect->getAlipayOpenId($channelId, $params['auth_code']);
        } else {
            if (empty($params['code'])) {
                throw new BadRequestHttpException('missing params');
            }
            $openIdInfo = $this->getOriginAndOpenId($params);
            $origin = $openIdInfo['origin'];
            $openId = $openIdInfo['openId'];
        }

        $redirect = !empty($type) ? base64_decode($param) : '';

        $str = (strpos($redirect, '?') !== false) ? '&' : '?';
        $redirectUrl = $redirect . $str . "channelId=$channelId&openId=$openId&origin=$origin";
        $channel = Channel::getByChannelId($channelId);
        $token = Token::createForMobile($channel->accountId);
        $this->setAccessToken($token->accessToken);
        if ($this->mustSubscribe($redirectUrl)) {
            $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $channelId);
            if (empty($follower) || (isset($follower['subscribed']) && $follower['subscribed'] != true)) {
                return $this->redirect($this->getSubscribePage($origin, $channelId, $type, $redirectUrl));
            }
        }
        return $this->redirect($redirectUrl);
    }

    /**
     * Get openid with oauth params
     * @param Array $params
     * @example :
     *      wechat  ['code' = 'a4vc32', 'appid' => 'wx2df5d7e4ce8a04ca']
     *      weibo   ['code' = 'a4vc32']
     * @throws BadRequestHttpException
     * @return Array  ['origin' => 'wechat', 'openId' => 'oC9Aes9vuisNRmC4ZNdIXY1lb_rk']
     */
    private function getOriginAndOpenId($params)
    {
        $channelId = $params['state'];
        $code = $params['code'];

        $channel = Channel::getByChannelId($channelId);
        $origin = $channel->origin;

        if ($origin == Member::WECHAT) {
            $wechatTestAppSecret = null;
            if ($channel->isTest) {
                // wechat test account's appId won't be returned by OAuth, has to fetch it from weconnect
                $channelInfo = Yii::$app->weConnect->getAccounts($channelId);
                $appId = $channelInfo[0]['appId'];
                $wechatTestAppSecret = $channelInfo[0]['appSecret'];
            } else {
                $appId = $params['appid'];
            }

            $result = Yii::$app->weConnect->getOpenId($code, $appId, $wechatTestAppSecret);

            if (!empty($result['openid']) || !empty($result['access_token'])) {
                $openId = $result['openid'];
            } else {
                throw new BadRequestHttpException('missing param');
            }
        } else {
            $result = Yii::$app->weiboConnect->getAccessToken($code);
            $openId = $result['uid'];
        }

        return ['origin' => $origin, 'openId' => $openId];
    }

    private function buildRedirectUrl($channelId, $oauthRedirect, $originRequired = false)
    {
        if (empty($channelId)) {
            throw new BadRequestHttpException('missing channel id');
        }
        $weChannels = Yii::$app->weConnect->getAccounts($channelId);
        if (empty($weChannels)) {
            throw new BadRequestHttpException('Invalid channel id');
        }
        $channelType = $weChannels[0]['channel'];
        $appId = $weChannels[0]['appId'];
        $state = $channelId;
        $redirectUrl = $oauthRedirect;

        switch ($channelType) {
            case Account::WECONNECT_CHANNEL_WEIXIN:
                $channel = Channel::getByChannelId($channelId);
                $redirectUrl = urlencode($redirectUrl);
                if ($channel->isTest) {
                    $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_base&state=$state#wechat_redirect";
                } else {
                    $component = Yii::$app->weConnect->getComponentToken();
                    $componentToken = $component['componentToken'];
                    $componentAppId = $component['componentAppId'];
                    $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_base&state=$state&component_appid=$componentAppId#wechat_redirect";
                }

                break;

            case Account::WECONNECT_CHANNEL_WEIBO:
                $redirectUrl .= (false === strpos($redirectUrl, '?')) ? "?state=$state" : "&state=$state";
                $weiboAppKey = WEIBO_APP_KEY;
                $url = "https://api.weibo.com/oauth2/authorize?client_id=$weiboAppKey&response_type=code&redirect_uri=$redirectUrl&scope=snsapi_base";
                break;

            case Account::WECONNECT_CHANNEL_ALIPAY:
                $redirectUrl .= (false === strpos($redirectUrl, '?')) ? "?state=$state" : "&state=$state";
                $redirectUrl = $redirectUrl . '&appId=' . $appId;
                $redirectUrl = urlencode($redirectUrl);
                $url = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?"
                          ."app_id=$appId&auth_skip=false&scope=auth_base&redirect_uri=$redirectUrl";
                break;
        }

        LogUtil::info(['message' => 'oauth2 snsapi_base redirecturl', 'url' => $redirectUrl], 'channel');

        return $url;
    }

    /**
     * Get oauth redirect url
     * @param string $baseUrl
     * @param string $redirectType
     * @param string $redirect
     * @throws BadRequestHttpException
     * @return string
     */
    private function buildOAuthRedirect($baseUrl, $redirectType, $redirect)
    {
        $redirectUrl = $baseUrl;
        if (!empty($redirect)) {
            $redirect = urldecode($redirect);
            $redirect = trim($redirect);
            if (!preg_match(StringUtil::URL_REGREX, $redirect)) {
                throw new BadRequestHttpException("invalid redirect url: $redirect");
            }
            substr($redirect, -1) == '/' ? $redirect = substr($redirect, 0, -1) : $redirect = $redirect;
            //base64 because of redirect query string
            $redirect = base64_encode($redirect);
            $redirectUrl = $redirectUrl . '/' . $redirectType . '/' . $redirect;
        }

        return $redirectUrl;
    }

    public function actionUserInfo($type = '', $param = '')
    {
        $params = $this->getQuery();

        if (empty($params['code']) || empty($params['appid']) || empty($params['state'])) {
            throw new BadRequestHttpException('missing params');
        }
        $code = $params['code'];
        $appId = $params['appid'];

        $channelId = $params['state'];
        $redirect = ($type == self::TYPE_REDIRECT) ? base64_decode($param) : '';
        $redirect = urlencode($redirect);

        $result = Yii::$app->weConnect->getOpenId($code, $appId);
        $unionId = '';
        $refreshToken = '';
        if (!empty($result['openid']) && !empty($result['access_token'])) {
            $openId = $result['openid'];
            $wxaccesstoken = $result['access_token'];
            $refreshToken = $result['refresh_token'];
            $follower = Yii::$app->weConnect->getUserInfo($wxaccesstoken, $openId);

            // add follower information to weconnect.
            $addUserInfo = Yii::$app->weConnect->setUserInfo($channelId, $follower);
            LogUtil::info(['info' => 'setUserInfo function', 'result' => $addUserInfo], 'member');

            $unionId = empty($follower['unionid']) ? '' : $follower['unionid'];
        } else {
            throw new BadRequestHttpException('missing param');
        }

        $mainDomain = UrlUtil::getDomain();
        $redirectUrl = $mainDomain . '/mobile/member';
        if ($type == self::TYPE_REDIRECT) {
            $redirectUrl .= '/activate';
        } else {
            $redirectUrl .= '/center';
        }
        $redirectUrl .= "?openId=$openId&channelId=$channelId&unionId=$unionId&redirect=$redirect&appId=$appId&redirectType=$type";

        $this->redirect($redirectUrl);
    }


    /**
     * Get default card by openId.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/mobile/card<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for get default card by openId.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "id": "54adecfee9c2fb720b8b456c",
     *      "name": "普通会员卡",
     *      "poster": "http://vincenthou.qiniudn.com/d67498fa8885c80c469c3ce3.jpg",
     *      "fontColor": "",
     *      "privilege": "<p><span style=\"color: rgb(51, 51, 51); font-family: arial; font-size: 13px; </p>",
     *      "condition": {
     *          "minScore": 0,
     *          "maxScore": "10"
     *      },
     *      "usageGuide": "<p><span style=\"color: rgb(51, 51, 51); font-family: arial; font-size: 13px;</p>",
     *      "isEnabled": true,
     *      "isDefault": true,
     *      "provideCount": 0,
     *      "createdAt": "2015-01-08 10:35:42",
     *      "updatedAt": "2015-01-08 10:35:42"
     *  }
     * </pre>
     */
    public function actionCard()
    {
        $channelId = $this->getQuery('channelId');
        if (empty($channelId)) {
            throw new BadRequestHttpException('missing channelId');
        }

        $channel = Channel::getEnableByChannelId($channelId);
        $accountId = $channel->accountId;
        $card = MemberShipCard::getDefault($accountId);

        return $card;
    }

    /**
     * Get score rules.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/mobile/score-rule<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for get score rules.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     where: json string, where = {"isEnabled" : true}
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "items": [
     *         {
     *             "id": "54b34be2e9c2fb61298b4568",
     *             "name": "birthday",
     *             "type": "time",
     *             "score": "100",
     *             "triggerTime": "week",
     *             "description": "",
     *             "isEnabled": true,
     *             "startTime": "",
     *             "endTime": "",
     *             "times": 0,
     *             "memberCount": 0
     *         },
     *         {
     *             "id": "54b34be2e9c2fb61298b4569",
     *             "name": "first_card",
     *             "type": "time",
     *             "score": "200",
     *             "triggerTime": "day",
     *             "description": "<p>首次开卡送200积分</p>",
     *             "isEnabled": true,
     *             "startTime": "",
     *             "endTime": "",
     *             "times": 0,
     *             "memberCount": 0
     *         }
     *     ],
     *     "_links": {
     *         "self": {
     *             "href": "http://wm.com/api/mobile/score-rule?channelId=54add01be4b026aee36dd26e&where=%7B%22isEnabled%22%3Atrue%7D&page=1"
     *         }
     *     },
     *     "_meta": {
     *         "totalCount": 2,
     *         "pageCount": 1,
     *         "currentPage": 0,
     *         "perPage": 20
     *     }
     * }
     * </pre>
     */
    public function actionScoreRule()
    {
        $channelId = $this->getQuery('channelId');
        $where = $this->getQuery('where');

        $channel = Channel::getEnableByChannelId($channelId);
        $accountId = $channel->accountId;

        if (!empty($where)) {
            $where = Json::decode($where, true);
        }

        return ScoreRule::search($accountId, $where);
    }

    /**
     * Follower bind to be a member.
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/mobile/bind<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for follower bind.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     mobile: string<br/>
     *     openId: string<br/>
     *     unionId: string<br/>
     *     channelId: string<br/>
     *     captcha: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "message": "OK",
     *  "data": "http://wm.com?memberId=55d29e86d6f97f72618b4569"
     * </pre>
     */
    public function actionBind()
    {
        //set language zh_cn when bind
        Yii::$app->language = LanguageUtil::LANGUAGE_ZH;

        $params = $this->getParams();
        if (empty($params['mobile']) || empty($params['openId']) ||
            empty($params['channelId']) || empty($params['captcha'])) {
            throw new BadRequestHttpException('missing param');
        }

        $isTest = false;
        if ($params['mobile'] == self::TEST_PHONE && $params['captcha'] == self::TEST_CODE) {
            $isTest = true;
        } else {
            $this->attachBehavior('CaptchaBehavior', new CaptchaBehavior);
            $this->checkCaptcha($params['mobile'], $params['captcha']);
        }

        //get accountId
        $openId = $params['openId'];
        $channel = Channel::getEnableByChannelId($params['channelId']);
        $origin = $channel->origin;
        $accountId = $channel->accountId;

        //create accessToken
        $token = Token::createForMobile($accountId);
        if (empty($token['accessToken'])) {
            throw new ServerErrorHttpException('Failed to create token for unknown reason.');
        }
        $accessToken = $token['accessToken'];
        $this->setAccessToken($accessToken);

        //if member has bind
        if (empty($params['unionId'])) {
            $member = Member::getByOpenId($openId);
        } else {
            $unionId = $params['unionId'];
            $member = Member::getByUnionid($unionId);
        }

        if (!empty($member)) {
            $memberId = (string) $member->_id;
            $url = $this->buildBindRedirect($memberId, $params);
            return ['message' => 'OK', 'data' => $url];
        }

        $follower = Yii::$app->weConnect->getFollowerByOriginId($openId, $params['channelId']);
        $originScene = empty($follower['subscribeSource']) ? '' : $follower['subscribeSource'];

        //check mobile has been bind
        $member = Member::getByMobile($params['mobile'], new \MongoId($accountId));
        if (!empty($member)) {
            $social = [
                'channel' => $params['channelId'],
                'openId' => $openId,
                'origin' => $origin,
                'originScene' => $originScene
            ];
            $this->addNewSocial($member, $social);
            $memberId = (string) $member->_id;
            $url = $this->buildBindRedirect($memberId, $params);
            return ['message' => 'OK', 'data' => $url];
        }

        //init avatar and location
        $avatar = !empty($follower['headerImgUrl']) ? $follower['headerImgUrl'] : Yii::$app->params['defaultAvatar'];
        $location = [];

        !empty($follower['city']) ? $location['city'] = $follower['city'] : null;
        !empty($follower['province']) ? $location['province'] = $follower['province'] : null;
        !empty($follower['country']) ? $location['country'] = $follower['country'] : null;

        LogUtil::info(['message' => 'get follower info', 'follower' => $follower], 'channel');
        //init member properties
        $propertiesInfo = Follower::formatPropertis($accountId, $follower);
        $properties = $propertiesInfo['properties'];
        //get default card
        $card = MemberShipCard::getDefault($accountId);

        $memberId = new \MongoId();
        $memberAttribute = [
            '$set' => [
                '_id' => $memberId,
                'avatar' => $avatar,
                'cardId' => $card['_id'],
                'phone' => $params['mobile'],
                'location' => $location,
                'score' => 0,
                'socialAccountId' => $params['channelId'],
                'properties' => $properties,
                'openId' => $openId,
                'origin' => $origin,
                'originScene' => $originScene,
                'unionId' => empty($unionId) ? '' : $unionId,
                'accountId' => $accountId,
                'cardNumber' => Member::generateCardNumber($card['_id']),
                'cardProvideTime' => new \MongoDate(),
                'createdAt' => new \MongoDate(),
                'socials' => [],
                'isDeleted' => false
            ]
        ];

        if (!$isTest) {
            $result = Member::updateAll($memberAttribute, ['accountId' => $accountId, 'openId' => $openId], ['upsert' => true]);
        } else {
            $result = true;
        }

        if ($result) {
            // reword score first bind card
            $this->attachBehavior('MemberBehavior', new MemberBehavior);
            $this->updateItemByScoreRule(Member::findByPk($memberId));

            Yii::$app->qrcode->create(UrlUtil::getDomain(), Qrcode::TYPE_MEMBER, $memberId, $accountId);
            $memberId = (string) $memberId;

            //remove follower
            Follower::removeByOpenId($accountId, $openId);

            $url = $this->buildBindRedirect($memberId, $params);
            return ['message' => 'OK', 'data' => $url];
        } else {
            LogUtil::error(['error' => 'member save error', 'params' => Json::encode($member)], 'member');
            throw new ServerErrorHttpException("bind fail");
        }
    }

    private function buildBindRedirect($memberId, $params)
    {
        $channelId = $params['channelId'];
        if (empty($params['redirectType']) || empty($params['redirect'])) {
            $url = UrlUtil::getDomain() . "/mobile/member/center?memberId=$memberId&channelId=$channelId";
        } else if ($params['redirectType'] == self::TYPE_REDIRECT) {
            $redirect = urldecode($params['redirect']);
            $str = (strpos($redirect, '?') !== false) ? '&' : '?';
            $url = $redirect . $str . "quncrm_member=$memberId";
        } else if ($params['redirectType'] == self::TYPE_REDIRECT_INSIDE) {
            $redirect = urldecode($params['redirect']);
            $str = (strpos($redirect, '?') !== false) ? '&' : '?';
            $url = $redirect . $str . "memberId=$memberId&channelId=$channelId";
        } else {
            LogUtil::error(['error' => 'bind redirect error', 'params' => $params], 'member');
        }

        return $url;
    }

    /**
     * Check must subscrib
     * @param string $origin
     * @param string $redirect
     * @return boolean
     */
    private function mustSubscribe($redirect)
    {
        if (strpos($redirect, 'mustSubscribe=1') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get subscribe page url
     * @param string $origin
     * @param string $channelId
     */
    private function getSubscribePage($origin, $channelId, $redirectType = '', $redirect = '')
    {
        $domain = UrlUtil::getDomain();
        switch ($origin) {
            case Member::WECHAT:
                $qrcodeUrl = Qrcode::getAttentionQrcode($channelId);
                $url = $domain . '/mobile/common/attention?imageUrl=' . $qrcodeUrl;
                break;
            case Member::WEIBO:
                $url =  $domain . '/mobile/common/wbattention?channelId=' . $channelId;
                break;
            case Member::ALIPAY:
                //TODO:
                $url = '/mobile/common/403';
                break;
            default:
                $url = '/mobile/common/404';
                break;
        }

        return $url;
    }

    public function actionPay()
    {
        $channelId = $this->getQuery('channelId');
        $redirect = $this->getQuery('redirect');

        if (empty($channelId)) {
            throw new BadRequestHttpException('missing params channelId');
        }

        $redirect = empty($redirect) ? '' : urlencode($redirect);

        $baseUrl = UrlUtil::getDomain(). '/api/mobile/check-pay';
        $oauthRedirect = $this->buildOAuthRedirect($baseUrl, self::TYPE_REDIRECT, $redirect);
        $channel = Channel::getByChannelId($channelId);
        if (empty($channel)) {
            throw new BadRequestHttpException('Invalid channelId');
        }
        $accountId = $channel->accountId;

        $url = Yii::$app->weConnect->buildPayOauthUrl((string)$accountId, $oauthRedirect, $channelId);
        $this->redirect($url);
    }

    public function actionCheckPay($type = '', $param = '')
    {
        $params = $this->getQuery();
        $channelId = $params['state'];

        if (empty($params['code']) || empty($channelId)) {
            throw new BadRequestHttpException('missing params');
        }

        $channel = Channel::getByChannelId($channelId);
        if (empty($channel)) {
            throw new BadRequestHttpException('Invalid channelId');
        }
        $accountId = $channel->accountId;

        $token = Token::createForMobile($accountId);
        $this->setAccessToken($token->accessToken);

        $openId = Yii::$app->weConnect->getPayOauthOpenId((string)$accountId, $params['code']);

        $redirect = !empty($type) ? base64_decode($param) : '';

        $str = (strpos($redirect, '?') !== false) ? '&' : '?';
        $redirectUrl = $redirect . $str . "channelId=$channelId&openId=$openId";
        return $this->redirect($redirectUrl);
    }
}
