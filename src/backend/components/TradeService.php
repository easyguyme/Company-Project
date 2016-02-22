<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use yii\web\BadRequestHttpException;
use backend\utils\StringUtil;
use backend\utils\LogUtil;

/**
 * This class is used for trade payment.
 *
 * @author Mike Wang <mikewang@augmentum.com.cn>
 */
class TradeService extends Component
{

    public $weconnectDomain;

    public function init()
    {
        //TODO
    }

    /**
     * Place an order
     *
     * Request Method:
     *
     *     POST
     *
     * Request Parameters:
     *
     *     {
     *          "quncrmAccountId": "群脉账号",
     *          "buyerId": "买家微信openId",
     *          "spbillCreateIp": "买家终端IP",
     *          "subject": "订单标题",
     *          "detail": "订单详情",
     *          "outTradeNo": "商户订单号",
     *          "totalFee": "订单金额/分",
     *          "timeExpire": "订单过期时间/时间戳秒",
     *     }
     *
     * Response Body:
     *
     *  {
     *   "code": 200,
     *   "message": "OK",
     *   "data": {
     *       "channelType": "WECHAT / ALIPAY",
     *       "quncrmAccountId": "群脉账号",
     *       "tradeType": "支付类型 JSAPI(公众号支付)，NATIVE(扫码支付)",
     *       "sellerId":"微信商户ID / 卖家支付宝账户号",
     *       "buyerId":"买家微信openId / 买家支付宝账户号",
     *       "spbillCreateIp": "买家终端IP",
     *       "subject": "订单标题",
     *       "detail": "订单详情",
     *       "outTradeNo": "商户订单号",
     *       "tradeNo": "微信订单号 / 支付宝订单号",
     *       "totalFee": "订单金额",
     *       "createTime": "订单创建时间",
     *       "timeExpire": "订单过期时间",
     *       "extension": {
     *           "wechatAppId": "微信公众号ID",
     *           "prepayId": "微信预支付交易会话标识",
     *           "codeUrl": "二维码链接, tradeType为NATIVE是有返回，可将该参数值生成二维码展示出来进行扫码支付"
     *       },
     *       "tradeStatus": "订单状态",
     *       "tradeStateDesc": "交易状态描述",
     *       "failureCode": "微信/支付宝 错误代码",
     *       "failureMsg": "微信/支付宝 错误代码描述"
     *   }
     */
    public function unifiedOrder(
        $subject,
        $orderNumber,
        $totalFee,
        $timeExpire,
        $clientIp,
        $accountId,
        $openId,
        $metadata,
        $detail = '',
        $tradeType = 'JSAPI'
    ) {
        $data = [
            'quncrmAccountId' => (string)$accountId,
            'tradeType'       => $tradeType,
            'buyerId'         => $openId,
            'spbillCreateIp'  => $clientIp,
            'subject'         => $subject,
            'detail'          => $detail,
            'outTradeNo'      => $orderNumber,
            'totalFee'        => $totalFee,
            'timeExpire'      => $timeExpire,
            'metadata'        => $metadata
        ];
        LogUtil::info(['order data' => $data], 'reservation');
        $url = $this->weconnectDomain . '/weixin/orders';
        return json_decode(Yii::$app->curl->postJson($url, json_encode($data)), true);
    }

    /**
     * Get wehcat pay signature
     *
     * Request Method:
     *
     *     POST
     *
     * Request Parameters:
     *
     *     {
     *         "quncrmAccountId": "群脉账号ID",
     *         "params": {
     *             "key1": "value1",
     *             "key2": "value2",
     *             "key3": "value3",
     *         }
     *     }
     *
     * Response Body:
     *
     *      {
     *           "code": 200,
     *           "message": "OK",
     *           "data": {
     *               "appId": "公众账号ID",
     *               "signType": "DSA、RSA、MD5",
     *               "paySign": "signString"
     *           }
     *       }
     * @return [type] [description]
     */
    public function getWechatPaySignature($accountId, $prepayId, $appId)
    {
        $url = $this->weconnectDomain . '/weixin/pay/sign';
        $timestamp = time();
        $nonceStr = StringUtil::rndString(16, StringUtil::ALL_DIGITS_LETTERS);
        $params = [
            'timeStamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'package'   => "prepay_id=$prepayId",
            'appId'     => $appId,
            'signType'  => 'MD5'
        ];
        $data = [
            'quncrmAccountId' => (string)$accountId,
            'params' => $params
        ];
        LogUtil::info(['wechat signature data' => $data], 'reservation');
        $result = Yii::$app->curl->postJson($url, json_encode($data));
        $result = json_decode($result, true);
        LogUtil::info(['wechat signature result' => $result], 'reservation');
        if (!empty($result) && $result['code'] === 200 && !empty($result['data'])) {
            $params['appId']    = $result['data']['appId'];
            $params['signType'] = $result['data']['signType'];
            $params['paySign']  = $result['data']['paySign'];
            return $params;
        }
    }

    public function getOrder($orderNumber)
    {
        $url = $this->weconnectDomain . "/weixin/orders/outTradeNo/$orderNumber";
        return json_decode(Yii::$app->curl->get($url), true);
    }

    /**
     * subject  String  订单标题    Yes
     * tradeStatus String  订单状态    Yes
     * createTimeFrom  String  下单时间    Yes
     * createTimeTo    String  下单时间    Yes
     * @return [type] [description]
     */
    public function getOrders($params)
    {
        $url = $this->weconnectDomain . '/weixin/orders';
        return json_decode(Yii::$app->curl->get($url, $params), true);
    }

    /**
     * {
     *     "quncrmAccountId": "群脉账号",
     *     "outTradeNo": "商户订单号",
     *     "outRefundNo": "商户退款单号",
     *     "totalFee": "订单金额",
     *     "refundFee": "退款金额"
     *     "opUserId": "操作员"
     * }
     * @return [type] [description]
     */
    public function refund($accountId, $orderNumber, $refundNumber, $totalFee, $refundFee, $opUserId)
    {
        $data = [
            'quncrmAccountId' => (string)$accountId,
            'outTradeNo'      => $orderNumber,
            'outRefundNo'     => $refundNumber,
            'totalFee'        => $totalFee,
            'refundFee'       => $refundFee,
            'opUserId'        => (string)$opUserId
        ];
        $url = $this->weconnectDomain . '/weixin/refunds';
        return json_decode(Yii::$app->curl->postJson($url, json_encode($data)), true);
    }

    public function getReund($refundNumber)
    {
        $url = $this->weconnectDomain . "/weixin/refunds/outRefundNo/$refundNumber";
        return json_decode(Yii::$app->curl->get($url), true);
    }

    public function getRefunds($params)
    {
        $url = $this->weconnectDomain . '/weixin/refunds';
        return json_decode(Yii::$app->curl->get($url, $params), true);
    }

    /**
     * The algorithm for unique code
     * @param  string $type   the type of code
     * @param  string $prefix the prefix for code
     * @return string $code   the unique code
     * @author Mike Wang
     */
    public function getUniqueCode($type = 'order', $prefix = 'P')
    {
        if (empty($type)) {
            $type = 'order';
        }
        $key = "$type-" . date('Ymd');
        $redis = Yii::$app->cache->redis;
        $code = $redis->incr($key);
        $code = $prefix . date('Ymd') . str_pad($code, 8, '0', STR_PAD_LEFT);
        $ttl = $redis->ttl($key);
        if ((int)$ttl < 0) {
            $redis->expire($key, (24 * 60 * 60));
        }
        return $code;
    }

    /**
     * Close order
     * @param  string $orderNumber
     */
    public function closeOrder($orderNumber)
    {
        $url = $this->weconnectDomain . "/weixin/orders/outTradeNo/$orderNumber";
        return json_decode(Yii::$app->curl->delete($url), true);
    }
}
