<?php
namespace backend\modules\product\models;

use Yii;
use MongoDate;
use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\components\ActiveDataProvider;
use backend\exceptions\InvalidParameterException;
use backend\modules\member\models\Member;

/**
 * Model class for couponLog.
 *
 * The followings are the available columns in collection 'couponLog':
 * @property MongoId    $_id
 * @property MongoId    $couponId
 * @property string     $type
 * @property string     $title
 * @property string     $status
 * @property object     $member:{id,name,phone}
 * @property object     $store:{id,name}
 * @property int        $total
 * @property MongoDate  $createdAt
 * @property MongoDate  $operationTime
 * @property MonogId    $accountId
 **/
class CouponLog extends PlainModel
{
    const RECIEVED = 'received';
    const REDEEMED = 'redeemed';
    const EXPIRED = 'expired';
    const DELETED = 'deleted';
    /**
     * Declares the name of the Mongo collection associated with couponLog.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'couponLog';
    }

    /**
     * Returns the list of all attribute names of couponLog.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'couponId', 'membershipDiscountId', 'type', 'title', 'status', 'member', 'store', 'total', 'operationTime'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'couponId', 'membershipDiscountId', 'type', 'title', 'status', 'member', 'store', 'total', 'operationTime'
            ]
        );
    }

    /**
     * Returns the list of all rules of couponLog.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [

            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into couponLog.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'couponId' => function () {
                    $couponId = (string)$this->couponId;
                    return $couponId;
                },
                'membershipDiscountId' => function () {
                    return (string)$this->membershipDiscountId;
                },
                'type', 'title', 'status',
                'member' => function () {
                    $member = $this->member;
                    $member['id'] .= '';
                    return $member;
                },
                'store', 'total',
                'operationTime' => function () {
                    return MongodbUtil::MongoDate2String($this->operationTime);
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt);
                }
            ]
        );
    }

    /**
    * Conversion format for couponLog.
    * @param $coupon Object
    * @param $member Object
    * @param $tokenInfo Object
    */
    public static function transformCouponLog($coupon, $member, $membershipDiscountId)
    {
        $memberItem = Member::getMemberInfo($member->_id, ['name', 'tel']);
        $memberInfo = [
            'id' => $member->_id,
            'name' => empty($memberItem['name']) ? '' : $memberItem['name'],
            'phone' => empty($memberItem['tel']) ? '' : $memberItem['tel'],
        ];
        $couponLog = new CouponLog();
        $couponLog->couponId = $coupon->_id;
        $couponLog->membershipDiscountId = $membershipDiscountId;
        $couponLog->type = $coupon->type;
        $couponLog->title = $coupon->title;
        $couponLog->status = self::RECIEVED;
        $couponLog->member = $memberInfo;
        $couponLog->total = 1;
        $couponLog->operationTime = new \MongoDate();
        $couponLog->createdAt = new \MongoDate();
        $couponLog->accountId = $coupon->accountId;
        return $couponLog;
    }

    /**
     * Search for coupon recieved, redeemed,deleted records.
     * @param array $params The search condition
     * @param string $accountId
     * @return array The couponLog list for recieved, redeemed,deleted records
     */
    public static function search($params, $accountId)
    {
        $condition = ['accountId' => $accountId, 'status' => $params['status']];
        $query = CouponLog::find();
        if (!empty($params['startTime'])) {
            $startTime = MongodbUtil::msTimetamp2MongoDate($params['startTime']);
            $condition['operationTime']['$gte'] = $startTime;
        }
        if (!empty($params['endTime'])) {
            $endTime = MongodbUtil::msTimetamp2MongoDate($params['endTime']);
            $condition['operationTime']['$lte'] = $endTime;
        }
        if (!empty($params['searchKey'])) {
            $key = $params['searchKey'];
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new \MongoRegex("/$key/i");
            $condition['$or']= [
                    ['member.name' => $keyReg],
                    ['member.phone' => $keyReg],
                    ['title' => $keyReg],
                ];
        }
        if (empty($params['orderBy'])) {
            $orderBy = ['operationTime' => SORT_DESC];
        } else {
            switch ($params['orderBy']) {
                case 'asc':
                    $orderBy = ['operationTime' => SORT_ASC];
                    break;

                default:
                    $orderBy = ['operationTime' => SORT_DESC];
                    break;
            }
        }
        $query = $query->where($condition)->orderBy($orderBy);
        $searchQuery = ['query' => $query];
        return new ActiveDataProvider($searchQuery);
    }

    /**
     * Get coupon stats by date
     * @param string $date, '2015-08-12'
     * @return array
     */
    public static function getStats($date)
    {
        $condition = [
            'operationTime' => [
                '$gte' => new MongoDate(strtotime($date)),
                '$lt' => new MongoDate(strtotime($date . ' +1 day'))
            ]
        ];
        $pipeline = [
            ['$match' => $condition],
            [
                '$group' => [
                    '_id'=> ['accountId' => '$accountId', 'couponId' => '$couponId', 'status' => '$status'],
                    'count' => ['$sum' => 1],
                ]
            ],
            [
                '$project' => [
                    'count' => 1,
                    '_id' => 0,
                    'accountId' => '$_id.accountId',
                    'couponId' => '$_id.couponId',
                    'status' => '$_id.status'
                ]
            ],
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
     * format the coupon struct when data get from getStats function
     * @param $datas, array
     * @return array
     */
    public static function formatStruct($datas)
    {
        $result = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $key = $data['couponId'] . '_' . $data['status'];
                $result[$key] = [
                    'count' => $data['count'],
                    'couponId' => $data['couponId'],
                    'accountId' => $data['accountId'],
                ];
            }
        }
        return $result;
    }

    /**
     * @return array
     * @param $ruleName,string
     * @param $memberIds, array
     * @param $accountId, MongoId
     * @param $timeStamp, int
     */
    public static function getAllMemberIdByRuleName($ruleName, $memberIds, $accountId, $timeStamp = 0)
    {
        $where = [
            'member.receiveType' => $ruleName,
            'accountId' => $accountId,
        ];

        if (!empty($memberIds)) {
            $where['member.id'] = ['$in' => $memberIds];
        }

        if (!empty($timeStamp)) {
            $where['createdAt'] = ['$gte' => new MongoDate($timeStamp)];
        }

        return self::distinct('member.id', $where);
    }

    public static function getLogByRuleName($ruleName, $memberId, $accountId)
    {
        $where = [
            'member.receiveType' => $ruleName,
            'member.id' => $memberId,
            'accountId' => $accountId,
        ];
        return self::findOne($where);
    }

    /**
     * Get member recived times
     * @param MongoId $accountId
     * @param MemberId $memberId
     * @param CouponId $couponId
     */
    public static function countMemberReciveByCouponId($accountId, $memberId, $couponId)
    {
        $condition = [
            'accountId' => $accountId,
            'couponId' => $couponId,
            'member.id' => $memberId,
        ];
        return self::count($condition);
    }

    public static function getTotalTimesByRuleName($ruleName, $accountId)
    {
        $where = [
            'member.receiveType' => $ruleName,
            'accountId' => $accountId
        ];
        return self::count($where);
    }
}
