<?php
namespace backend\modules\channel\models;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use MongoRegex;
use MongoId;
use MongoDate;
use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\StringUtil;
use backend\utils\TimeUtil;
use backend\exceptions\ApiDataException;
use backend\exceptions\InvalidParameterException;
use backend\components\ActiveDataProvider;
use Exception;
use backend\utils\LogUtil;
use backend\utils\ExcelUtil;

/**
 * Model class for TradePayment.
 * The followings are the available columns in collection 'tradePayment':
 * @property MongoId $_id
 * @property string $orderNumber
 * @property string $transactionId  wechat payment order number
 * @property array  $user {memberId: ObjectId, openId:string, channelId:string, origin:string, name:string, telephone:string}
 * @property string $expectedAmount
 * @property string $realAmount
 * @property string $couponCode
 * @property string couponDiscount
 * @property string $subject the order name
 * @property string $payMode
 * @property string $payAccount
 * @property string $status
 * @property MongoDate $timeExpire
 * @property MongoDate $paymentTime
 * @property MongoDate $createdAt
 * @property MongoId   $accountId
 **/
class TradePayment extends PlainModel
{
    //Reservation pay mode
    const PAY_MODE_ALIPAY = 'alipay';
    const PAY_MODE_WECHAT = 'wechat';
    const PAY_MODE_CASH   = 'cash';
    const PAY_MODE_BANK   = 'bank';

    const STATUS_PREPARE = 'prepare';
    const STATUS_TIMEOUT = 'timeout';
    const STATUS_PAID    = 'paid';
    const STATUS_FAILED  = 'failed';

    /**
    * Declares the name of the Mongo collection associated with TradePayment.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'tradePayment';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['orderNumber', 'transactionId', 'user', 'expectedAmount', 'realAmount', 'subject', 'payMode', 'payAccount', 'status', 'timeExpire', 'paymentTime', 'couponCode', 'couponDiscount']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['orderNumber', 'transactionId', 'user', 'expectedAmount', 'realAmount', 'subject', 'payMode', 'payAccount', 'status', 'timeExpire', 'paymentTime', 'couponCode', 'couponDiscount']
        );
    }

    /**
    * Returns the list of all rules of TradePayment.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['orderNumber', 'user', 'expectedAmount', 'payMode'], 'required'],
                ['payMode', 'in', 'range' =>
                    [
                        self::PAY_MODE_WECHAT,
                        self::PAY_MODE_ALIPAY,
                        self::PAY_MODE_CASH,
                        self::PAY_MODE_BANK
                    ]
                ],
                ['status', 'in', 'range' =>
                    [
                        self::STATUS_PREPARE,
                        self::STATUS_TIMEOUT,
                        self::STATUS_PAID,
                        self::STATUS_FAILED
                    ]
                ],
                ['status', 'default', 'value' => self::STATUS_PREPARE]
            ]
        );
    }

    /**
     * Get the payment transitionId, only the succeed payment has transitionId.
     * @param  string   $orderNumber
     * @param  ObjectId $accountId
     * @return string   $transitionId
     */
    public static function getTransactionId($orderNumber, $accountId)
    {
        $condition = [
            'orderNumber' => $orderNumber,
            'accountId'   => $accountId,
            'status'      => self::STATUS_PAID
        ];
        $payment = static::findOne($condition);
        if (empty($payment)) {
            throw new BadRequestHttpException(Yii::t('channel', 'unpaid_order'));
        }
        return $payment->transactionId;
    }

    public static function getAllStatus()
    {
        return [
            self::STATUS_PREPARE,
            self::STATUS_TIMEOUT,
            self::STATUS_PAID,
            self::STATUS_FAILED
        ];
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into TradePayment.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'orderNumber', 'transactionId', 'expectedAmount', 'realAmount', 'subject', 'payMode', 'payAccount', 'status', 'couponCode', 'couponDiscount',
                'user' => function () {
                    $user = $this->user;
                    if (!empty($user['memberId'])) {
                        $user['memberId'] = (string)$user['memberId'];
                    }
                    return $user;
                },
                'timeExpire' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                },
                'paymentTime' => function () {
                    return MongodbUtil::MongoDate2String($this->paymentTime);
                },
            ]
        );
    }

    /**
     * Avoid duplicate payment
     * @param  string $orderNumber The order number
     * @param  string $accountId   The account id
     * @return
     */
    public static function avoidDuplicate($orderNumber)
    {
        $condition = [
            'orderNumber' => $orderNumber,
            'status' => self::STATUS_PAID
        ];
        $payment = self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
        if (empty($payment)) {
            return true;
        }
        if (MongodbUtil::isExpired($payment->timeExpire)) {
            return true;
        }
        throw new BadRequestHttpException(Yii::t('channel', 'illegal_request'));
    }

    public static function isAlreadyPrepay($orderNumber)
    {
        $condition = [
            'orderNumber' => $orderNumber,
            'status' => self::STATUS_PREPARE
        ];
        $payment = self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
        if (empty($payment)) {
            return false;
        }
        if (MongodbUtil::isExpired($payment->timeExpire)) {
            return false;
        }
        return true;
    }

    /**
     * Unified Order, get prepay_id and save prepay message to tradePayment.
     * @param  ObjectId $accountId
     * @param  array $order  The info of order
     */
    public static function create($accountId, $order)
    {
        self::avoidDuplicate($order['orderNumber'], $accountId);

        $payment = new self;
        $payment->payAccount = empty($order['payAccount']) ? '' : $order['payAccount'];
        $payment->orderNumber = $order['orderNumber'];
        $payment->subject = $order['subject'];
        $payment->expectedAmount = number_format($order['expectedAmount'], 2, '.', '');
        $payment->realAmount = number_format($order['realAmount'], 2, '.', '');
        $payment->payMode = $order['payMode'];
        $payment->user = $order['user'];
        $payment->timeExpire = $order['timeExpire'];
        $payment->accountId = $accountId;
        $payment->couponCode = empty($order['couponCode']) ? '' : $order['couponCode'];
        $payment->couponDiscount =  empty($order['couponDiscount']) ? '' : $order['couponDiscount'];
        $payment->paymentTime = new MongoDate();
        if (!$payment->save()) {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
        return true;
    }

    /**
     * Update Order if have exists a same orderNumber tradePayment record.
     * @param  ObjectId $accountId
     * @param  array $order  The info of order
     */
    public static function updatePayment($accountId, $order)
    {
        $payment = static::findOne(['orderNumber' => $order['orderNumber'], 'accountId' => $accountId]);
        if (empty($payment)) {
            return;
        }
        $payment->couponCode = empty($order['couponCode']) ? '' : $order['couponCode'];
        $payment->couponDiscount =  empty($order['couponDiscount']) ? '' : $order['couponDiscount'];
        $payment->realAmount = $order['realAmount'];
        $payment->paymentTime = new MongoDate();
        if (!$payment->save(true, ['couponCode', 'couponDiscount', 'realAmount', 'paymentTime'])) {
            return false;
        }
        return true;
    }

    public static function getSearchCondition($accountId, $params)
    {
        $condition = ['accountId' => $accountId, 'status' => self::STATUS_PAID, 'payMode' => self::PAY_MODE_WECHAT];

        if (!empty($params['searchKey'])) {
            $searchKey = StringUtil::regStrFormat($params['searchKey']);
            $searchKey = new MongoRegex("/$searchKey/i");
            $condition['transactionId'] = $searchKey;
        }

        if (!empty($params['startTime'])) {
            $condition['paymentTime']['$gte'] = new MongoDate(TimeUtil::ms2sTime($params['startTime']));
        }

        if (!empty($params['endTime'])) {
            $condition['paymentTime']['$lt'] = new MongoDate(TimeUtil::ms2sTime($params['endTime']));
        }
        return $condition;
    }

    public static function search($accountId, $params)
    {
        $query = self::find();
        $condition = self::getSearchCondition($accountId, $params);
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);

        return new ActiveDataProvider(['query' => $query]);
    }

    public static function isPaid($accountId, $orderNumber)
    {
        $condition = [
            'orderNumber' => $orderNumber,
            'accountId' => $accountId,
            'status' => self::STATUS_PAID
        ];
        $payment = static::findOne($condition);
        if (!empty($payment)) {
            return true;
        }
        return false;
    }

    public static function preProcessData($condition, $header, $filePath)
    {
        $condition['pageSize'] = 1000;
        $condition['pageNum'] = 1;

        try {
            $tradePaymentList = Yii::$app->tradeService->getOrders($condition);
        } catch (Exception $e) {
            LogUtil::error(['message' => 'Faild to get trade payment', 'error' => $e->getMessage()], 'trade_reque');
            return false;
        }
        $datas = isset($tradePaymentList['data']['results']) ? $tradePaymentList['data']['results'] : [];

        if (empty($datas)) {
            return false;
        }

        while (!empty($datas)) {
            $rows = [];
            foreach ($datas as $data) {
                $rows[] = [
                    'tradeNo' => "'" . $data['tradeNo'],
                    'outTradeNo' => !empty($data['metadata']['orderNumber']) ? $data['metadata']['orderNumber'] : $data['outTradeNo'],
                    'buyerNickname' => isset($data['extension']['buyerNickname']) ? $data['extension']['buyerNickname'] : '',
                    'totalFee' => $data['totalFee'] / 100,
                    'subject' => $data['subject'],
                    'paymentTime' => date('Y-m-d H:i:s', $data['paymentTime'] / 1000),
                ];
            }
            ExcelUtil::exportCsv($header, $rows, $filePath, 1);

            $condition['pageNum'] += 1;
            unset($data, $datas);
            try {
                $tradePaymentList = Yii::$app->tradeService->getOrders($condition);
            } catch (Exception $e) {
                LogUtil::error(['message' => 'Faild to get trade payment in second way', 'error' => $e->getMessage()], 'trade_reque');
                unset($tradePaymentList);
                return false;
            }
            $datas = isset($tradePaymentList['data']['results']) ? $tradePaymentList['data']['results'] : [];
        }
        return true;
    }

    /**
     * Record offline payment message.
     * @param  array $paymentInfo
     */
    public static function offlinePayment($paymentInfo, $accountId)
    {
        LogUtil::info(['paymentInfo' => $paymentInfo], 'reservation');
        $payment = new self;
        $payment->accountId = $accountId;
        $payment->orderNumber = $paymentInfo['orderNumber'];
        $payment->user = $paymentInfo['user'];
        $payment->status = self::STATUS_PAID;
        $payment->payMode = $paymentInfo['payMode'];
        $payment->expectedAmount = $paymentInfo['expectedAmount'];
        $payment->realAmount = empty($paymentInfo['realAmount']) ? 0 : $paymentInfo['realAmount'];
        $payment->payAccount = $paymentInfo['payAccount'];
        $payment->paymentTime = new MongoDate(TimeUtil::ms2sTime($paymentInfo['paymentTime']));
        $payment->subject = $paymentInfo['subject'];
        $payment->couponCode = empty($paymentInfo['couponCode']) ? '' : $paymentInfo['couponCode'];
        $payment->couponDiscount =  empty($paymentInfo['couponDiscount']) ? '' : $paymentInfo['couponDiscount'];
        return $payment->save();
    }
}
