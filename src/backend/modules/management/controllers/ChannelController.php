<?php
namespace backend\modules\management\controllers;

use Yii;
use mongoId;
use backend\models\Token;
use backend\models\User;
use backend\models\Account;
use backend\modules\helpdesk\models\HelpDeskSetting;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\exceptions\WechatUnauthException;
use backend\exceptions\FailedResponseApiException;
use backend\exceptions\ApiDataException;
use yii\helpers\Json;
use backend\models\Channel;
use backend\utils\LogUtil;
use backend\exceptions\InvalidParameterException;
use backend\models\WebhookEvent;
use backend\utils\UrlUtil;

/**
 * Wechat document reference:
 * https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&lang=zh_CN
 *
 * Webo document reference:
 * https://api.weibo.com/oauth2/authorize?client_id=123050457758183&redirect_uri=http://www.example.com/response&response_type=code
 */

class ChannelController extends BaseController
{
    //const for default rule
    const SOCIAL_CHANNEL_WECHAT = '微信';
    const SOCIAL_CHANNEL_WEIBO = '微博';
    const SOCIAL_CHANNEL_ALIPAY = '支付宝';
    const WECHAT_PAYMENT_DIRECTORY = 'webapp/common/pay/';
    const PAYMENT_NATIVE = 'NATIVE';

    //const for weibo account
    const WEIBO_NORMAL_ACCOUNT = 'NORMAL_ACCOUNT';
    const WEIBO_AUTH_ACCOUNT = 'AUTH_ACCOUNT';

    /**
     * Remove bound a channel account
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/management/channel/{channelaccountId}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for removing bound a channel account.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelAccount: string, the channel account id<br/>
     *     channelType: string, channel type("weibo" , "wechat", ...)<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the delete result, 0 means query fail, 1 means delete successfully<br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     data: array, json array to deleted channel detail information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "channelAccount": "gh_fdba39256c8e",
     *     "channelType": "wechat",
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'data' : []
     * }
     * </pre>
     */
    public function actionDelete($id)
    {
        $accountId = $this->getAccountId();
        $params = $this->getParams();
        if (empty($params['type']) || !in_array($params['type'], [Channel::WEIBO, Channel::ALIPAY])) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        $channelType = $params['type'];

        // Recovery micro-blog authorized access token
        if ($channelType == Channel::WEIBO) {
            $token = $params['weiboToken'];
            Yii::$app->weiboConnect->revokeWeiboToken($token);
        }

         //refine to call wechat-connection system api(Delete One Account)
        $result = Yii::$app->weConnect->deleteAccount($id);

        if ($result) {
            if (!Channel::disableByChannelIds($accountId, [$id])) {
                throw new \yii\web\ServerErrorHttpException("delete account channel failed");
            }
            if (false === HelpDeskSetting::updateAll(['$pull' => ['channels' => ['id' => $id]]], ['accountId' => $accountId])) {
                throw new \yii\web\ServerErrorHttpException("delete helpdesk setting channel failed");
            }
        } else {
            throw new \yii\web\ServerErrorHttpException("delete channel failed");
        }

        return [];
    }

    /**
     * Api for Wechat callback
     **/
    public function actionCreateWechat()
    {
        try {
            $currentDomain = UrlUtil::getDomain();

            //get pre auth code
            $authCode = $this->getQuery('auth_code');
            $component = Yii::$app->weConnect->getComponentToken();
            $componentToken = $component['componentToken'];
            $componentAppId = $component['componentAppId'];

            //get auth information
            $auth = Yii::$app->weConnect->getQueryAuth($componentToken, $authCode, $componentAppId);

            //get the authorizer's infomation
            $authorizer = Yii::$app->weConnect->getAuthorizerInfo($componentToken, $componentAppId, $auth['authorizerAppId']);
            $account = [
                "appId" => $authorizer['appid'],
                "refreshToken" => $auth['authorizerRefreshToken'],
                "channelAccount" => $authorizer['wechatId'],
                "name" => $authorizer['nickname'],
                "channel" => "WEIXIN",
                "accountType" => $this->_getAccountType($authorizer['type'], $authorizer['verified']),
                "headImageUrl" => $authorizer['headImg']
            ];

            //create a new account
            $info = Yii::$app->weConnect->createAccount($account);
            $channelId = $info['id'];

            //update account information and insert the new channel
            $accountId = $this->getAccountId();

            $createChannelResult = Channel::upsert($accountId, $channelId, Channel::WECHAT, $account['name'], $account['accountType'], false, $authorizer['appid']);
            if (!$createChannelResult) {
                throw new \yii\web\ServerErrorHttpException("update channel failed");
            }

            //initialize the default rules
            $types = ['SUBSCRIBE', 'RESUBSCRIBE', 'DEFAULT'];
            $this->_initDefaultRules($channelId, $types, $account['name'], self::SOCIAL_CHANNEL_WECHAT);

            $this->redirect($currentDomain . '/management/channel');
        } catch (FailedResponseApiException $e) {
            $this->redirect($currentDomain . '/management/channel?errmsg=' . urlencode($e->getMessage()));
        } catch (WechatUnauthException $e) {
            $this->redirect($currentDomain . '/management/channel?errmsg=' . urlencode(Yii::t('channel', 'wechat_account_unauth')));
        } catch (\Exception $e) {
            $this->redirect($currentDomain . '/management/channel?errmsg=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Api for Weibo callback
     **/
    public function actionCreateWeibo()
    {
        try {
            $currentDomain = UrlUtil::getDomain();
            $accountId = $this->getAccountId();

            //get authorization code code
            $authCode = $this->getQuery('code');

            // get oauth2/access_token
            $token = Yii::$app->weiboConnect->getAccessToken($authCode);

            // get oauth2/get_token_info, such as uid, appkey, create_at, expire_in...
            $tokenInfo = Yii::$app->weiboConnect->getBindWeiboUUID($token["access_token"]);

            // get bind weibo info
            $weiboAccount = Yii::$app->weiboConnect->getBindWeiboInfo($token["access_token"], $tokenInfo['uid']);
            $account = Account::findOne(['_id' => $accountId]);

            $channelAccountIds = Channel::getWeiboByAccount($accountId);
            $updateAccount = null;

            if (count($channelAccountIds) > 0) {
                $result = Yii::$app->weConnect->getAccounts($channelAccountIds);
                foreach ($result as $item) {
                    if (!empty($item['channel']) && $weiboAccount['id'] == $item['channelAccount']) {
                        $updateAccount = $item;
                    }
                }
            }

            $info = null;

            // In order to make sure a logical token expire time (subtract 1 day)
            $weiboTokenExpireTime = ($tokenInfo["create_at"] + $tokenInfo["expire_in"] - 24 * 60 * 60) * 1000;
            $account = [
                "channelAccount" => $weiboAccount['id'],
                "appId" => $weiboAccount['id'],
                "weiboToken" => $token["access_token"],
                "weiboTokenExpireTime" => $weiboTokenExpireTime,
                "name" => $weiboAccount['screen_name'],
                "channel" => "WEIBO",
                "headImageUrl" => $weiboAccount['profile_image_url'],
                "weiboAccountType" => $weiboAccount["verified"] ? self::WEIBO_AUTH_ACCOUNT : self::WEIBO_NORMAL_ACCOUNT
            ];
            if (!empty($updateAccount)) {
                //update a account
                $info = Yii::$app->weConnect->updateAccount($updateAccount["id"], $account);
            } else {
                //validate if the count of the channel has exceeded the limitation
                $limit = $this->_getChannelLimit();
                $channelCount = Channel::getEnableCountByAccountId($accountId);
                if ($channelCount >= $limit) {
                    throw new BadRequestHttpException(Yii::t('channel', 'channel_count_limit'));
                }
                //create a new account
                $info = Yii::$app->weConnect->createAccount($account);

                //initialize the default rules
                $types = ['SUBSCRIBE', 'RESUBSCRIBE', 'DEFAULT'];
                $this->_initDefaultRules($info['id'], $types, $account['name'], self::SOCIAL_CHANNEL_WEIBO);
            }

            $channelId = $info['id'];
            //update account information and insert the new channel

            $createChannelResult = Channel::upsert($accountId, $channelId, Channel::WEIBO, $account['name'], '', false, $weiboAccount['id']);
            if (!$createChannelResult) {
                throw new \yii\web\ServerErrorHttpException("update channel failed");
            }

            return ['data' => $info];
        } catch (\Exception $e) {
            return ['errmsg' => urlencode($e->getMessage())];
        }
    }

    public function actionInitAlipay()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        if (empty($params['name']) || empty($params['appId']) || empty($params['headImageUrl'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $account = [
            'name' => $params['name'],
            'appId' => $params['appId'],
            'channel' => Account::WECONNECT_CHANNEL_ALIPAY,
            'channelAccount' => $params['appId'],
            'headImageUrl' => $params['headImageUrl'],
            'description' => empty($params['description']) ? '' : $params['description']
        ];
        if (empty($params['id'])) {
            //validate if the count of the channel has exceeded the limitation
            $limit = $this->_getChannelLimit();
            $channelCount = Channel::getEnableCountByAccountId($accountId);
            if ($channelCount >= $limit) {
                throw new BadRequestHttpException(Yii::t('channel', 'channel_count_limit'));
            }
            //create a new account
            $info = Yii::$app->weConnect->createAccount($account);
            $channelId = $info['id'];
            //initialize the default rules
            $types = ['SUBSCRIBE', 'RESUBSCRIBE', 'DEFAULT'];
            $this->_initDefaultRules($channelId, $types, $account['name'], self::SOCIAL_CHANNEL_ALIPAY);
        } else {
            $info = Yii::$app->weConnect->updateAccount($params['id'], $account);
            $channelId = $info['id'];
        }

        $createChannelResult = Channel::upsert($accountId, $channelId, Channel::ALIPAY, $account['name'], '', false, $params['appId']);
        if (!$createChannelResult) {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }

        return $info;
    }

    public function actionBindWeibo()
    {
        $accessToken = $this->getAccessToken();
        $bindPath = Yii::$app->weiboConnect->getAuthorizeCodePath($accessToken);

        return $bindPath;
    }

    public function actionBindWechat()
    {
        //validate if the count of the channel has exceeded the limitation
        $limit = $this->_getChannelLimit();
        $accountId = $this->getAccountId();
        $channelCount = Channel::getEnableCountByAccountId($accountId);

        if ($channelCount >= $limit) {
            throw new BadRequestHttpException(Yii::t('channel', 'channel_count_limit'));
        }

        $component = Yii::$app->weConnect->getComponentToken();
        $componentToken = $component['componentToken'];
        $componentAppId = $component['componentAppId'];
        // $componentToken = "abc123123123";
        // $componentAppId = "wx3930d6279611b5c5";
        $accessToken = $this->getAccessToken();

        $redirectUrl = UrlUtil::getDomain() . '/api/management/channel/create-wechat';
        $redirectUrl = urlencode($redirectUrl);

        //get pre auth code
        $preCode = Yii::$app->weConnect->getPreauthcode($componentToken, $componentAppId);
        return ["bindPath" => "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=$componentAppId&pre_auth_code=$preCode&redirect_uri=$redirectUrl&componenttoken=$componentToken"];
    }

    /**
     * Make weibo access
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/management/channel/weibo-access<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used make weibo access.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     fansServiceToken: string, the fansServiceToken<br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "id": "54f51cefe4b0c5896e262375",
     *      "appId": "2131440262",
     *      "name": "我只是来潜水的",
     *      "channelAccount": "2131440262",
     *      "headImageUrl": "http://tp3.sinaimg.cn/2131440262/50/5658132246/1",
     *      "channel": "WEIBO",
     *      "status": "ENABLE",
     *      "createTime": 1425349871347,
     *      "weiboToken": "2.00kySP1C0KN_bWff39b48c4crlurBC",
     *      "weiboTokenExpireTime": 1427741997000,
     *      "fansServiceToken": "2.00kySP1C0aiipk76cb330220j6C7EE"
     * }
     * </pre>
     */
    public function actionWeiboAccess()
    {
        $fansServiceToken = $this->getParams('fansServiceToken');
        $channelId = $this->getChannelId();

        if (empty($fansServiceToken)) {
            throw new InvalidParameterException(['fansToken' => \Yii::t('common', 'required_filed')]);
        }

        $channelInfo = Yii::$app->weConnect->updateWeiboFansServiceToken($channelId, $fansServiceToken);

        return $channelInfo;
    }


    /**
     * Create test wechat account
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/management/channel/create-test-wechat<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create test wechat account.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     * <pre>
     *{
     *      "name":"唯1のse彩",
     *      "appId":"wx5c208e957d25a9fe",
     *      "appSecret":"79ac3d410703781ece8e99f162419b48",
     *      "originalId":"gh_3beb200f225e"
     * }
     * </pre>
     */
    public function actionCreateTestWechat()
    {
        $params = $this->getParams();
        $account = [
            "name" => $params['name'],
            "channelAccount" => $params['originalId'],
            "appId" => $params['appId'],
            "appSecret" => $params['appSecret'],
            "channel" => "WEIXIN",
            "accountType" => "SERVICE_AUTH_ACCOUNT"
        ];
        //create a new account
        $info = Yii::$app->weConnect->createAccount($account);
        $channelId = $info['id'];

        //update account information and insert the new channel
        $accountId = $this->getAccountId();

        $createChannelResult = Channel::upsert($accountId, $channelId, Channel::WECHAT, $account['name'], $account['accountType'], true, $params['appId']);
        if (!$createChannelResult) {
            throw new ServerErrorHttpException("Update channel failed");
        }

        //initialize the default rules
        $types = ['SUBSCRIBE', 'RESUBSCRIBE', 'DEFAULT'];
        $this->_initDefaultRules($channelId, $types, $account['name'], self::SOCIAL_CHANNEL_WECHAT);

        return ['data' => $info];
    }

    /**
     * List all test wechat accounts
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/management/channel/test-wechat-list<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create test wechat account.
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * [
     *     {
     *         accountType: "SUBSCRIPTION_ACCOUNT"
     *         appId: "wx5c208e957d25a9fe"
     *         appSecret: "79ac3d410703781ece8e99f162419b48"
     *         channel: "WEIXIN"
     *         channelAccount: "gh_3beb200f225e"
     *         createTime: 1431597000381
     *         encodingAESKey: "1QW34RDFB567UI34DGT60OWSMFJKE432WASXLPO0I7R"
     *         id: "55546fc8e4b0d8376000b858"
     *         name: "唯1のse彩"
     *         serviceUrl: "http://dev.wx.quncrm.com/wechat/wx5c208e957d25a9fe"
     *         status: "ENABLE"
     *         token: "XF3E2FF34DFD4457FASAF34565FDA3562"
     *     }
     * ]
     * </pre>
     */
    public function actionTestWechatList()
    {
        //update account information and insert the new channel
        $accountId = $this->getAccountId();

        $account = Account::findByPk($accountId);
        $result = [];
        $testWechatIds = Channel::getWechatByAccount($accountId, true);
        if (!empty($testWechatIds)) {
            $result = Yii::$app->weConnect->getAccounts($testWechatIds);
        }

        return $result;
    }

    /**
     * Delete test wechat account
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/management/channel/delete-test-wechat<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to delete test wechat account.
     * <br/><br/>
     *
     * <b>Request Params:</b>
     * <pre>
     * {
     *     "accountId": "55546fc8e4b0d8376000b858"
     * }
     * </pre>
     */
    public function actionDeleteTestWechat()
    {
        $wechatAccountId = $this->getParams('accountId');
        $accountId = $this->getAccountId();

        if (empty($wechatAccountId)) {
            throw new BadRequestHttpException(\Yii::t('common', 'parameters_missing'));
        }

        $result = Yii::$app->weConnect->deleteAccount($wechatAccountId);
        Channel::disableByChannelIds($accountId, [$wechatAccountId]);

        return $result;
    }

    /**
     * Get the channel limitaion
     * @return int
     * @author Devin.Jin
     **/
    private function _getChannelLimit()
    {
        return Yii::$app->params['channelLimit'];
    }

    /**
     * Initialize the default rules for new binded channel
     * @param $channelId string channel UUID
     * @param $types array type for default rules
     * @param $accountName string wechat account nickname
     * @author Vincent Hou
     **/
    private function _initDefaultRules($channelId, $types, $accountName, $socialChannel)
    {
        foreach ($types as $type) {
            $message = Yii::$app->params['default_' . strtolower($type) . '_reply'];
            $content = str_replace(['{name}', '{channel}'], [$accountName, $socialChannel], $message);
            Yii::$app->weConnect->initDefaultRule($channelId, ['type' => $type, 'status' => 'ENABLE', 'msgType' => 'TEXT', 'content' => $content]);
        }
    }

    private function _getAccountType($serviceType, $verified)
    {
        if ($serviceType == '2') {
            return $verified ? 'SERVICE_AUTH_ACCOUNT' : 'SERVICE_ACCOUNT';
        } else {
            return $verified ? 'SUBSCRIPTION_AUTH_ACCOUNT' : 'SUBSCRIPTION_ACCOUNT';
        }
    }

    public function actionOpenWechatPayment()
    {
        $params = $_POST;
        $file = $_FILES;
        $accountId = $this->getAccountId();

        if (empty($params['appId']) || empty($params['sellerId'])
            || empty($params['apiKey']) || empty($file['p12Credential'])
            || empty($file['pemCredentialKey']) || empty($file['pemCredential'])
            || empty($file['caCredential']) || empty($params['weconnectAccountId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $p12CredentialResult = Yii::$app->file->setCurlFile($file['p12Credential']);
        $pemCredentialKeyResult = Yii::$app->file->setCurlFile($file['pemCredentialKey']);
        $pemCredentialResult = Yii::$app->file->setCurlFile($file['pemCredential']);
        $caCredentialResult = Yii::$app->file->setCurlFile($file['caCredential']);

        $condition = [
            'appId' => $params['appId'],
            'sellerId' => $params['sellerId'],
            'apiKey' => $params['apiKey'],
            'weconnectAccountId' => $params['weconnectAccountId'],
            'quncrmAccountId' => (string)$accountId,
            'p12Credential' => $p12CredentialResult,
            'pemCredentialKey' => $pemCredentialKeyResult,
            'pemCredential' => $pemCredentialResult,
            'caCredential' => $caCredentialResult,
            'paymentStatus' => 'ENABLE'
        ];

        $result = Yii::$app->weConnect->openWechatPayment($condition);

        if (!empty($result) && empty($result['message']) == 'OK') {
            $channelId = $params['weconnectAccountId'];
            LogUtil::info(['accountId' => (string)$accountId, 'channelId' => $channelId], 'reservation');
            Yii::$app->webhookEvent->subscribeMsg('channel', $channelId, WebhookEvent::DATA_TYPE_MSG_PAYMENT, time());
            return ['authDirectory' => DOMAIN . self::WECHAT_PAYMENT_DIRECTORY];
        } else {
            LogUtil::error(['message' => 'Failed to open wechat payment', 'data' => $result], 'channel');
        }
    }

    public function actionWechatPaymentMessage()
    {
        $result = [];
        $accountId = $this->getAccountId();
        $result = Yii::$app->weConnect->getWechatPaymentMessage($accountId);
        $result['authDirectory'] = DOMAIN . self::WECHAT_PAYMENT_DIRECTORY;
        return $result;
    }

    public function actionEditWechatPayment()
    {
        $params = $_POST;
        $file = $_FILES;
        $accountId = $this->getAccountId();

        if (empty($params['appId']) || empty($params['sellerId'])
            || empty($params['apiKey']) || empty($params['weconnectAccountId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $condition = [
            'appId' => $params['appId'],
            'sellerId' => $params['sellerId'],
            'apiKey' => $params['apiKey'],
            'paymentStatus' => 'ENABLE',
            'weconnectAccountId' => $params['weconnectAccountId'],
            'quncrmAccountId' => (string)$accountId,
            'p12Credential' => empty($file['p12Credential']) ? null : Yii::$app->file->setCurlFile($file['p12Credential']),
            'pemCredentialKey' => empty($file['pemCredentialKey']) ? null : Yii::$app->file->setCurlFile($file['pemCredentialKey']),
            'pemCredential' => empty($file['pemCredential']) ? null : Yii::$app->file->setCurlFile($file['pemCredential']),
            'caCredential' => empty($file['caCredential']) ? null : Yii::$app->file->setCurlFile($file['caCredential']),
            'p12CredentialId' => empty($params['p12CredentialId']) ? '' : $params['p12CredentialId'],
            'pemCredentialId' => empty($params['pemCredentialId']) ? '' : $params['pemCredentialId'],
            'pemCredentialKeyId' => empty($params['pemCredentialKeyId']) ? '' : $params['pemCredentialKeyId'],
            'caCredentialId' => empty($params['caCredentialId']) ? '' : $params['caCredentialId'],
        ];

        $result = Yii::$app->weConnect->openWechatPayment($condition);
        return $result;
    }

    public function actionCheckPayment()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $ip = Yii::$app->request->getUserIP();
        if (empty($params['price'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $metadata = [
            'isTest'         => true,
            'expectedAmount' => $params['price'],
            'channelId'      => Yii::$app->weConnect->getWechatPaymentConfigedChannelId((string)$accountId),
        ];
        $condition = [
            'quncrmAccountId' => (string)$accountId,
            'tradeType'       => self::PAYMENT_NATIVE,
            'productId'       => '1',
            'spbillCreateIp'  => $ip,
            'subject'         => 'order',
            'detail'          => 'detail',
            'outTradeNo'      => Yii::$app->tradeService->getUniqueCode(),
            'totalFee'        => (int)($params['price'] * 100),
            'timeExpire'      => strtotime('next Monday') * 1000,
            'metadata'        => $metadata
        ];

        $result = Yii::$app->weConnect->checkPayment($condition);
        return $result;
    }

    public function actionCheckRefund()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $account = Account::findByPk($accountId);

        if (empty($params['outTradeNo']) || empty($params['refundFee'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $metadata = [
            'isTest'         => true,
            'expectedAmount' => number_format(intval($params['refundFee']) / 100, 2, '.', ''),
            'subject'        => 'order',
            'channelId'      => Yii::$app->weConnect->getWechatPaymentConfigedChannelId((string)$accountId),
        ];
        $condition = [
            'quncrmAccountId' => (string)$accountId,
            'outTradeNo'      => $params['outTradeNo'],
            'outRefundNo'     => $params['outTradeNo'],
            'totalFee'        => (int)$params['refundFee'],
            'refundFee'       => (int)$params['refundFee'],
            'opUserId'        => $account['name'],
            'metadata'        => $metadata
        ];
        $result = Yii::$app->weConnect->checkRefund($condition);
        return $result;
    }
}
