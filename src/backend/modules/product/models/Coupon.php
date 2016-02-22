<?php
namespace backend\modules\product\models;

use Yii;
use MongoRegex;
use MongoId;
use MongoDate;
use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\components\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use backend\models\Qrcode;
use backend\models\Channel;
use backend\utils\UrlUtil;

/**
 * Model class for Goods.
 * The followings are the available columns in collection 'Goods':
 * @property MongoId $_id
 * @property string $type
 * @property string $title
 * @property int $total
 * @property int $limit
 * @property string $tip
 * @property array $time:{type,beginTime.endTime}
 * @property string $picUrl
 * @property string $url
 * @property string  $description
 * @property string $usageNote
 * @property string $phone
 * @property string $storeType
 * @property array $store[{id,name,branchName,address,phome}]
 * @property array $qrcode{id,name,qiniuKey}
 * @property float $discountAmount
 * @property int $discountCondition
 * @property int $reductionAmount
 * @property MongoDate $createdAt
 * @property MongoId $accountId
 **/

class Coupon extends BaseModel
{
    //coupon type
    const COUPON_DISCOUNT = 'discount';
    const COUPON_CASH = 'cash';
    const COUPON_GIFT = 'gift';
    const COUPON = 'coupon';

    const COUPON_ABSOLUTE_TIME = 'absolute';
    const COUPON_RELATIVE_TIME = 'relative';

    const COUPON_ALL_STORE = 'all';
    const COUPON_SPECIFY_STORE = 'specify';

    const COUPON_QRCODE_RECEIVED = 'coupon';

    /**
    * Declares the name of the Mongo collection associated with Coupon.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'coupon';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['type','title','total','limit', 'tip', 'time', 'picUrl', 'url', 'description', 'usageNote', 'phone', 'storeType', 'stores', 'qrcodes', 'discountAmount', 'discountCondition', 'reductionAmount']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['type','title','total','limit', 'tip', 'time', 'picUrl', 'url', 'description', 'usageNote', 'phone', 'storeType', 'stores', 'qrcodes', 'discountAmount', 'discountCondition', 'reductionAmount']
        );
    }

    /**
    * Returns the list of all rules of coupon.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['type', 'title', 'total', 'picUrl', 'tip', 'time', 'usageNote', 'storeType'], 'required'],
                ['limit', 'default', 'value' => 1],
                ['stores', 'default', 'value' => []],
                ['qrcodes', 'default', 'value' => []],
                ['stores', 'converStore'],
                ['url', 'default', 'value' => ''],
                [['total', 'limit'], 'number', 'min'=> 0, 'integerOnly'=> true],
                ['url', 'default', 'value' => ''],
            ]
        );
    }

    public function converStore()
    {
        if (!empty($this->stores)) {
            $stores = $this->stores;
            foreach ($stores as $key => $store) {
                $stores[$key] = [
                    'id' => new MongoId($store['id']),
                    'name' => isset($store['name']) ? $store['name'] : '',
                    'branchName' => isset($store['branchName']) ? $store['branchName'] : '',
                    'address' => isset($store['address']) ? $store['address'] : '',
                    'phone' => isset($store['phone']) ? $store['phone'] : '',
                ];
            }
            $this->stores = $stores;
        }
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into coupon.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'type', 'title', 'total', 'limit',
                'time' => function () {
                    $time = $this->time;
                    if ($time['type'] == self::COUPON_ABSOLUTE_TIME) {
                        $time['beginTime'] = MongodbUtil::MongoDate2String($time['beginTime']);
                        $time['endTime'] = MongodbUtil::MongoDate2String($time['endTime']);
                    }
                    return $time;
                },
                'url', 'picUrl', 'tip', 'description', 'usageNote','phone', 'storeType',
                'stores' => function () {
                    $stores = $this->stores;
                    if (!empty($stores)) {
                        foreach ($stores as &$store) {
                            $store['id'] = (string)$store['id'];
                        }
                    }
                    return $stores;
                },
                'qrcodes' => function () {
                    $qrcodes = $this->qrcodes;
                    if (!empty($qrcodes)) {
                        foreach ($qrcodes as &$qrcode) {
                            $qrcode['id'] = (string)$qrcode['id'];
                            $qrcode['url'] = Yii::$app->qrcode->getUrl($qrcode['qiniuKey']);
                            unset($qrcode['qiniuKey']);
                        }
                    }
                    return $qrcodes;
                },
                'discountAmount', 'discountCondition', 'reductionAmount',
            ]
        );
    }

    /**
     * conver the coupon time
     */
    public static function converCouponTime($params)
    {
        if (!empty($params['time']['type']) && isset($params['time']['beginTime']) && isset($params['time']['endTime'])) {
            if (!in_array($params['time']['type'], [self::COUPON_ABSOLUTE_TIME, self::COUPON_RELATIVE_TIME])) {
                throw new InvalidParameterException(Yii::t('product', 'coupon_time_missing'));
            }
            if (Coupon::COUPON_ABSOLUTE_TIME == $params['time']['type']) {
                $params['time']['beginTime'] = new MongoDate(TimeUtil::ms2sTime($params['time']['beginTime']));
                $params['time']['endTime'] = new MongoDate(TimeUtil::ms2sTime($params['time']['endTime']));
            } else {
                $params['time']['beginTime'] = intval($params['time']['beginTime']);
                $params['time']['endTime'] = intval($params['time']['endTime']);
            }
        } else {
            throw new BadRequestHttpException(Yii::t('product', 'coupon_time_missing'));
        }
        return $params;
    }

    /**
     * check the store type
     */
    public static function checkCouponStore($params)
    {
        $storeType = [self::COUPON_ALL_STORE, self::COUPON_SPECIFY_STORE];
        if (empty($params['storeType']) || !in_array($params['storeType'], $storeType)) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }
    }

    /**
     * check the coupon field base on the coupon type
     */
    public static function checkCouponField($params)
    {
        if (empty($params['type'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }

        switch ($params['type']) {
            case self::COUPON_DISCOUNT:
                self::checkPhone($params);
                break;

            case self::COUPON_CASH:
                self::checkReductionAmount($params);
                break;

            case self::COUPON:
                self::checkDescription($params);
                self::checkPhone($params);
                break;
        }
    }

    public static function checkPhone($params)
    {
        if (empty($params['phone'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_phone'));
        }
    }

    public static function checkDescription($params)
    {
        if (empty($params['description'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_description'));
        }
    }

    public static function checkDiscountAmount($params)
    {
        if (empty($params['discountAmount'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_discountAmount'));
        }
    }

    public static function checkReductionAmount($params)
    {
        if (empty($params['reductionAmount'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_reductionAmount'));
        }
    }


    /**
     * search coupon
     */
    public static function search($params)
    {
        $query = Coupon::find();
        $condition = ['accountId' => $params['accountId'], 'isDeleted' => self::NOT_DELETED];

        if (!empty($params['title'])) {
            $key = $params['title'];
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new MongoRegex("/$key/i");
            $search = ['title' => $keyReg];
            $condition = array_merge($condition, $search);
            unset($search);
        }

        if (!empty($params['unexpired'])) {
            $time = new MongoDate(strtotime(TimeUtil::msTime2String($params['unexpired'], 'Y-m-d')));
            $search = [
                '$or' => [
                    [
                        'time.type' => self::COUPON_ABSOLUTE_TIME,
                        'time.endTime' => ['$gte' => $time]
                    ],
                    ['time.type' => self::COUPON_RELATIVE_TIME],
                ]
            ];
            $condition = array_merge($condition, $search);
            unset($search, $time);
        }
        if (!empty($params['notSoldOut'])) {
            $condition['total'] = ['$gt' => 0];
        }
        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition);

        $unlimited = Yii::$app->request->get('unlimited', false);
        if ($unlimited) {
            return ['items' => $query->all()];
        }
        return new ActiveDataProvider(['query' => $query]);
    }

    /**
     * delete coupon qrcode info
     * @param $qrcodes, array
     */
    public static function deleteCouponQrcode($qrcodes)
    {
        if (!empty($qrcodes)) {
            foreach ($qrcodes as $qrcode) {
                if (isset($qrcode['id']) && isset($qrcode['qiniuKey'])) {
                    //delete qiniu file
                    Yii::$app->qiniu->deleteFile($qrcode['qiniuKey']);
                    //delete qrcode info
                    Qrcode::deleteAll(['_id' => new MongoId($qrcode['id'])]);
                }
            }
        }
    }

    /**
     * conver source store info to coupon store struct
     * @param $object store
     * @param $converStoreId, whether to conver store id to string,if true,it will be convered to string
     */
    public static function conver2couponStore($stores, $converStoreId = true)
    {
        if (empty($stores)) {
            return [];
        }
        $data = [];
        foreach ($stores as $store) {
            $address = isset($store->location['province']) ? $store->location['province'] : '';
            $address .=  isset($store->location['city']) ? $store->location['city'] : '';
            $address .=  isset($store->location['district']) ? $store->location['district'] : '';
            $address .=  isset($store->location['detail']) ? $store->location['detail'] : '';

            $storeId = $converStoreId ? (string)$store->_id : $store->_id;

            $data[] = [
                'id' => $storeId,
                'name' => $store->name,
                'branchName' => $store->branchName,
                'phone' => $store->telephone,
                'address' => $address,
            ];
        }
        return $data;
    }

    /**
     * get coupon qrcode info,this function will return three arrays,
     * 1.old qrcode,2.new qrcode,3.show qrcode to fronted
     * @param $params, array
     * @param $coupon, object
     * @param $exitsQrcode, array
     */
    public static function getCouponQrcode($params, $coupon, $existsQrcode)
    {
        $data = $result = [];

        $channels = array_unique($params['channels']);
        $channelInfos = Channel::findAll(['channelId' => ['$in' => $channels]]);

        $existsChannelInfos = [];
        foreach ($channelInfos as $channelInfo) {
            $existsChannelInfos[$channelInfo['channelId']] = $channelInfo;
        }
        foreach ($channels as $channel) {
            //if the qrcode is exists in any channel,not need to create a new qrcode for this channel
            if (isset($existsQrcode[$channel])) {
                $qrcodeId = $existsQrcode[$channel]['id'];
                $origin = $existsQrcode[$channel]['origin'];
                $channelName = $existsQrcode[$channel]['channelName'];
                $qiniuKey = $existsQrcode[$channel]['qiniuKey'];
                unset($existsQrcode[$channel]);
            } else {
                //redirect url
                $mainDomain = UrlUtil::getDomain();
                $content = $mainDomain . '/api/mobile/coupon?channelId=' . $channel . '&couponId='. $params['couponId'];
                $qrcode = Yii::$app->qrcode->create($content, Coupon::COUPON_QRCODE_RECEIVED, $coupon->_id, $coupon->accountId, false);

                $qrcodeId = $qrcode->_id;
                $origin = $existsChannelInfos[$channel]['origin'];
                $channelName = $existsChannelInfos[$channel]['name'];
                $qiniuKey = $qrcode->qiniuKey;
            }

            $data[] = [
                'id' => $qrcodeId,
                'origin' => $origin,
                'channelName' => $channelName,
                'channelId' => $channel,
                'qiniuKey' => $qiniuKey,
            ];

            $result[$origin][] = [
                'id' => (string)$qrcodeId,
                'origin' => $origin,
                'channelName' => $channelName,
                'fileName' => $qiniuKey,
                'channelId' => $channel,
                'url' => Yii::$app->qrcode->getUrl($qiniuKey),
            ];
        }
        return [$existsQrcode, $data, $result];
    }
}
