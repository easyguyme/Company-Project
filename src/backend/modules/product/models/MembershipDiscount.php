<?php
namespace backend\modules\product\models;

use Yii;
use MongoId;
use MongoDate;
use backend\models\Qrcode;
use backend\components\PlainModel;
use backend\components\ActiveDataProvider;
use backend\utils\TimeUtil;
use backend\utils\MongodbUtil;
use backend\utils\StringUtil;
use backend\modules\product\models\Coupon;
use backend\modules\member\models\Member;
use backend\behaviors\MembershipDiscountBehavior;
use backend\behaviors\MemberBehavior;

/**
 * Model class for membershipDiscount.
 *
 * The followings are the available columns in collection 'membershipDiscount':
 * @property MongoId    $_id
 * @property object     $coupon:{id,title,picUrl,startTime,endTime,status}
 * @property string     $code
 * @property MongoId    $member{id,name}
 * @property Array      $qrcode:{id,name,qiniuKey}
 * @property MongoDate  $createdAt
 * @property MonogId    $accountId
 **/
class MembershipDiscount extends PlainModel
{
    const RECEIVE_COUPON = 'receive';

    const UNUSED = 'unused';
    const USED = 'used';
    const DELETED = 'deleted';
    const EXPIRED = 'expired';
    const ABSOLUTE = 'absolute';
    /**
     * Declares the name of the Mongo collection associated with membershipDiscount.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'membershipDiscount';
    }

    /**
     * Returns the list of all attribute names of membershipDiscount.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'coupon', 'code', 'member', 'qrcode'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'coupon', 'code', 'member', 'qrcode'
            ]
        );
    }

    /**
     * Returns the list of all rules of membershipDiscount.
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
     * The default implementation returns the names of the columns whose values have been populated into membershipDiscount.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'qrcode' => function () {
                    $qrcode = $this->qrcode;
                    if (!empty($qrcode)) {
                        $qrcode['_id'] = (string)$qrcode['_id'];
                        $qrcode['url'] = Yii::$app->qrcode->getUrl($qrcode['qiniuKey']);
                        unset($qrcode['qiniuKey']);
                    }
                    return $qrcode;
                },
                'code',
                'coupon' => function () {
                    $coupon = $this->coupon;
                    $coupon['id'] .= '';
                    $coupon['startTime'] = MongodbUtil::MongoDate2String($coupon['startTime'], 'Y-m-d H:i:s');
                    $coupon['endTime'] = MongodbUtil::MongoDate2String($coupon['endTime'], 'Y-m-d H:i:s');
                    return $coupon;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
            ]
        );
    }

    /**
     * create a code when user receive coupon
     * @param $accountId, MongoId
     */
    public static function getCouponCode($accountId)
    {
        $code = StringUtil::rndString(12, 1);
        $data = MembershipDiscount::findOne(['code' => $code, 'accountId' => $accountId]);
        if (empty($data)) {
            return $code;
        } else {
            return self::getCouponCode($accountId);
        }
    }

    /**
    * Conversion format for membershipDiscount.
    * @param $coupon Object
    * @param $member Object
    */
    public static function transformMembershipDiscount($coupon, $member, $receiveType = null)
    {
        if ($coupon->time['type'] == self::ABSOLUTE) {
            $startTime = $coupon->time['beginTime'];
            $endTime = $coupon->time['endTime'];
        } else {
            $startDate = TimeUtil::today() + 60 * 60 * $coupon->time['beginTime'] * 24;
            $startTime = new MongoDate($startDate);
            $endTime = new MongoDate($startDate + $coupon->time['endTime'] * 60 * 60* 24 - 1);
        }
        $couponItem = [
            'id' => $coupon->_id,
            'title' => $coupon->title,
            'picUrl' => $coupon->picUrl,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'status' => self::UNUSED,
            'type' => $coupon->type,
            'receiveType' => empty($receiveType) ? self::RECEIVE_COUPON : $receiveType,
            'discountAmount' => $coupon->discountAmount,
            'discountCondition' => $coupon->discountCondition,
            'reductionAmount' => $coupon->reductionAmount
        ];
        $code = self::getCouponCode($coupon->accountId);

        $hostInfo = self::getHostInfo();

        $qrcode = Yii::$app->qrcode->create($hostInfo, Coupon::COUPON_QRCODE_RECEIVED, $code, $coupon->accountId);
        $qrcodeItem = [
            '_id' => $qrcode->_id,
            'qiniuKey' => $qrcode->qiniuKey,
        ];

        //get member name
        $memberName = '';
        if (!empty($member->properties)) {
            foreach ($member->properties as $properties) {
                if ($properties['name'] == Member::DEFAULT_PROPERTIES_NAME) {
                    $memberName = $properties['value'];
                }
            }
        }
        $membershipDiscount = new MembershipDiscount();
        $membershipDiscount->qrcode = $qrcodeItem;
        $membershipDiscount->coupon = $couponItem;
        $membershipDiscount->member = [
            'id' => $member->_id,
            'name' => $memberName,
        ];
        $membershipDiscount->code = $code;
        $membershipDiscount->createdAt = new MongoDate();
        $membershipDiscount->accountId = $coupon->accountId;
        return $membershipDiscount;
    }

    public static function getHostInfo()
    {
        return rtrim(DOMAIN, '/');
    }

    /**
    * Conversion format for membershipDiscount.
    * @param $memberId MongoId
    * @param $status string
    *
    */
    public static function search($memberId, $status = null)
    {
        $query = self::find();

        $status = $status == null ? self::UNUSED : $status;
        $memberId = new MongoId($memberId);

        $search = [
            'member.id' => $memberId,
            'coupon.status' => $status
        ];

        //if the coupon is expired,no need to show
        $current = new MongoDate(strtotime(date('Y-m-d')));
        if (self::UNUSED == $status) {
            $search['coupon.endTime'] = ['$gte' => $current];
        }

        //if the coupon is expired
        if (self::EXPIRED == $status) {
            unset($search['coupon.status']);
            $search['$or'] = [
                ['coupon.status' => self::EXPIRED],
                ['coupon.status' => self::UNUSED, 'coupon.endTime' => ['$lt' => $current]]
            ];
        }
        $params['orderBy'] = "{'createdAt':'asc'}";
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($search);
        return new ActiveDataProvider(['query' => $query]);
    }

    public static function getTotalCashInfo($params)
    {
        $available = $unavailable = [];
        $current = new MongoDate(strtotime(date('Y-m-d') . '+1days'));
        if (!empty($params['couponType']) && $params['couponType'] == Coupon::COUPON_CASH && !empty($params['price'])) {
            //get available membershipdiscount
            $available = self::getAvailableMembershipDiscount($params, $current);
            //get unavailable membershipdiscount(expired and not start and not match price)
            $unavailable = self::getUnavailableMembershipDiscount($params, $current);
        }
        return ['available' => $available, 'unavailable' => $unavailable];
    }

    public static function getUnavailableMembershipDiscount($params, $current)
    {
        $where = [
            'member.id' => $params['memberId'],
            'coupon.type' => Coupon::COUPON_CASH,
            '$or' => [
                ['coupon.status' => self::EXPIRED],
                ['coupon.status' => self::UNUSED, 'coupon.startTime' => ['$gte' => $current]],
                ['coupon.status' => self::UNUSED, 'coupon.discountCondition' => ['$gt' => floatval($params['price'])]]
            ],
        ];
        return MembershipDiscount::findAll($where);
    }

    public static function getAvailableMembershipDiscount($params, $current)
    {

        $where = [
            'member.id' => $params['memberId'],
            'coupon.type' => Coupon::COUPON_CASH,
            'coupon.status' => self::UNUSED,
            'coupon.startTime' => ['$lt' => $current],
            '$or' => [
                ['coupon.discountCondition' => null],
                ['coupon.discountCondition' => ['$lte' => floatval($params['price'])]],
            ]
        ];
        return MembershipDiscount::findAll($where);
    }

    /**
    * Conversion format for membershipDiscount.
    * @param $memberId MongoId
    * @param $couponId MongoId
    *
    */
    public static function getCouponMessage($couponId, $memberId)
    {
        return self::findOne(['coupon.id' => $couponId, 'member.id' => $memberId]);
    }

    /**
     * Update member card after save member.
     * @see \yii\db\BaseActiveRecord::afterSave($insert, $changedAttributes)
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $this->attachBehavior('MembershipDiscountBehavior', new MembershipDiscountBehavior);
            $this->operationCoupon($this);
        }
    }
}
