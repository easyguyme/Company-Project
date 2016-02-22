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
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\components\ActiveDataProvider;
use Exception;
use backend\utils\ExcelUtil;
use backend\utils\LogUtil;

/**
 * Model class for TradeRefund.
 * The followings are the available columns in collection 'tradeRefund':
 * @property MongoId $_id
 * @property string $refundNumber
 * @property string $orderNumber
 * @property string $transactionId
 * @property array $admin{id:ObjectId,name: string}
 * @property array $user {memberId: ObjectId, openId:string, channelId:string, origin:string, name:string, telephone:string}
 * @property string $expectedAmount
 * @property string $realAmount
 * @property string $refundAccount
 * @property string $comments
 * @property MongoDate $refundAt
 * @property string $refundMode[alipay, wechat]
 * @property MongoDate $createdAt
 * @property MongoId   $accountId
 **/

class TradeRefund extends PlainModel
{
    //Trade refund status
    const REFUND_MODE_ALIPAY = 'alipay';
    const REFUND_MODE_WECHAT = 'wechat';
    const REFUND_MODE_CASH   = 'cash';
    const REFUND_MODE_BANK   = 'bank';

    /**
    * Declares the name of the Mongo collection associated with TradeRefund.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'tradeRefund';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['orderNumber', 'subject', 'transactionId', 'admin', 'expectedAmount', 'realAmount', 'refundAccount', 'comments', 'refundMode', 'refundNumber', 'refundAt', 'user']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['orderNumber', 'subject', 'transactionId', 'admin', 'expectedAmount', 'realAmount', 'refundAccount', 'comments', 'refundMode', 'refundNumber', 'refundAt', 'user']
        );
    }

    /**
    * Returns the list of all rules of tradeRefund.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['refundNumber', 'orderNumber', 'admin', 'expectedAmount', 'realAmount', 'refundMode'], 'required'],
                ['comments', 'default', 'value' => ''],
                ['refundMode', 'in', 'range' =>
                    [
                        self::REFUND_MODE_WECHAT,
                        self::REFUND_MODE_ALIPAY,
                        self::REFUND_MODE_CASH,
                        self::REFUND_MODE_BANK
                    ]
                ]
            ]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into tradeRefund.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'refundNumber', 'subject', 'transactionId', 'user', 'refundAccount',
                'admin' => function () {
                    $admin = $this->admin;
                    if (isset($admin['id'])) {
                        $admin['id'] = (string)$admin['id'];
                    }
                    return $admin;
                },
                'expectedAmount', 'realAmount', 'refundMode', 'comments', 'orderNumber',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'refundAt' => function () {
                    return MongodbUtil::MongoDate2String($this->refundAt, 'Y-m-d H:i:s');
                }
            ]
        );
    }

    public static function getSearchCondition($accountId, $params)
    {
        $condition = ['accountId' => $accountId];

        if (!empty($params['searchKey'])) {
            $searchKey = StringUtil::regStrFormat($params['searchKey']);
            $searchKey = new MongoRegex("/$searchKey/i");
            $condition['transactionId'] = $searchKey;
        }

        if (!empty($params['startTime'])) {
            $condition['refundAt']['$gte'] = new MongoDate(TimeUtil::ms2sTime($params['startTime']));
        }

        if (!empty($params['endTime'])) {
            $condition['refundAt']['$lt'] = new MongoDate(TimeUtil::ms2sTime($params['endTime']));
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

    public static function refund($accountId, $refundInfo)
    {
        $refund = new self;
        $refundNumber = Yii::$app->tradeService->getUniqueCode('', 'T');
        $refund->transactionId = empty($refundInfo['transactionId']) ? '' : $refundInfo['transactionId'];
        $refund->refundNumber = $refundNumber;
        $refund->accountId = $accountId;
        $refund->orderNumber = $refundInfo['orderNumber'];
        $refund->expectedAmount = $refundInfo['expectedAmount'];
        $refund->realAmount = $refundInfo['realAmount'];
        $refund->admin = $refundInfo['admin'];
        $refund->user = $refundInfo['user'];
        $refund->refundMode = $refundInfo['refundMode'];
        $refund->refundAt = empty($refundInfo['refundAt']) ? new MongoDate() : new MongoDate(TimeUtil::ms2sTime($refundInfo['refundAt']));
        $comments = empty($refundInfo['comments']) ? '' : $refundInfo['comments'];
        $refund->comments = $comments;
        $refund->subject = $refundInfo['subject'];

        return $refund->Save();
    }

    public static function preProcessData($condition, $header, $filePath)
    {
        $condition['pageSize'] = 10;
        $condition['pageNum'] = 1;

        try {
            $refunds = Yii::$app->tradeService->getRefunds($condition);
        } catch (Exception $e) {
            LogUtil::error(['message' => 'Faild to get refund info', 'error' => $e->getMessage()], 'trade_reque');
            return false;
        }
        $datas = isset($refunds['data']['results']) ? $refunds['data']['results'] : [];

        if (empty($datas)) {
            return false;
        }

        while (!empty($datas)) {
            $rows = [];
            foreach ($datas as $data) {
                $rows[] = [
                    'refundNo' => "'" . $data['refundNo'],
                    'buyerNickname' => isset($data['extension']['buyerNickname']) ? $data['extension']['buyerNickname'] : '',
                    'refundFee' => $data['refundFee'] / 100,
                    'subject' => isset($data['metadata']['subject']) ? $data['metadata']['subject'] : '',
                    'createTime' => date('Y-m-d H:i:s', $data['createTime'] / 1000),
                ];
            }
            ExcelUtil::exportCsv($header, $rows, $filePath, 1);

            $condition['pageNum'] += 1;
            try {
                $refunds = Yii::$app->tradeService->getRefunds($condition);
            } catch (Exception $e) {
                LogUtil::error(['message' => 'Faild to get refund info in second way', 'error' => $e->getMessage()], 'trade_reque');
                unset($data, $datas, $refunds);
                return false;
            }
            $datas = isset($refunds['data']['results']) ? $refunds['data']['results'] : [];
        }
        return true;
    }
}
