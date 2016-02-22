<?php
namespace backend\modules\member\models;

use backend\components\BaseModel;
use Yii;
use MongoId;
use backend\utils\MongodbUtil;
use backend\components\ActiveDataProvider;
use backend\utils\StringUtil;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use backend\exceptions\ApiDataException;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\exceptions\InvalidParameterException;
use backend\models\Channel;
use yii\web\ServerErrorHttpException;
use backend\components\Webhook;
use backend\modules\product\models\Coupon;
use backend\modules\product\models\MembershipDiscount;
use backend\modules\product\models\CouponLog;
use backend\models\Follower;
use yii\helpers\ArrayHelper;

/**
 * Model class for MemberShipCard.
 *
 * The followings are the available columns in collection 'memberShipCard':
 * @property ObjectId    $_id
 * @property string      $avatar
 * @property ObjectId    $cardId
 * @property Array       $location:{$country, $province, $city, $detail}
 * @property Array       $tags
 * @property Array       $properties:{$id, $name, $value}
 * @property string      $cardNumber
 * @property MongoDate   $cardProvideTime
 * @property Int64       $cardExpiredAt
 * @property string      $score
 * @property string      $socialAccountId
 * @property string      $openId
 * @property string      $unionId
 * @property ObjectId    $accountId
 * @property string      $origin
 * @property string      $remarks
 * @property boolean     $isDeleted
 * @property MongoDate   $createdAt
 * @property MongoDate   $updatedAt
 * @property Int64       $birth
 * @property Int64       $totalScore
 * @property Array       $socials
 * @property Boolean     $qrcodeViewed
 **/
class Member extends BaseModel
{
    //const for member default property name
    const DEFAULT_PROPERTIES_NAME = 'name';
    const DEFAULT_PROPERTIES_GENDER = 'gender';
    const DEFAULT_PROPERTIES_MOBILE = 'tel';
    const DEFAULT_PROPERTIES_BIRTHDAY = 'birthday';
    const DEFAULT_PROPERTIES_EMAIL = 'email';

    //const for card states
    const EXPIRED_IN_ONE_DAY = '1';
    const EXPIRED_IN_ONE_WEEK = '2';
    const EXPIRED = '3';

    const MONGO_ID_LENGTH = 24;

    //const for card number
    const MAX_CARD_NUMBER = 'omni_max_card_number';

    public static $defaultProperties = ['name', 'gender', 'tel', 'birthday', 'email'];

    public static $mapProperties = [
        '姓名' => 'name',
        '性别' => 'gender',
        '性別' => 'gender',
        '手机' => 'tel',
        '手機' => 'tel',
        '生日' => 'birthday',
        '邮箱' => 'email',
        '郵箱' => 'email',
        'phone' => 'tel',
        'mobile' => 'tel'
    ];

    public static $genderMap = [
        '男' => 'male',
        '女' => 'female',
        '未知' => 'unknown',
    ];

    /**
     * Declares the name of the Mongo collection associated with Member.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'member';
    }

    /**
     * Returns the list of all attribute names of member.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'avatar', 'cardId', 'location', 'cardNumber',
                'cardExpiredAt', 'cardProvideTime', 'unionId',
                'origin', 'originScene', 'socials', 'tags',
                'score', 'totalScore', 'properties', 'remarks',
                'socialAccountId', 'openId', 'birth', 'qrcodeViewed',
                'isDisabled', 'totalScoreAfterZeroed', 'phone'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'avatar', 'cardId', 'location', 'cardNumber',
                'cardExpiredAt', 'cardProvideTime', 'unionId',
                'origin', 'originScene', 'socials', 'tags',
                'score', 'totalScore', 'properties', 'remarks',
                'socialAccountId', 'openId', 'birth', 'qrcodeViewed',
                'isDisabled', 'totalScoreAfterZeroed', 'phone'
            ]
        );
    }

    /**
     * Returns the list of all rules of ChatMessage.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['cardId', 'origin'], 'required'],
                ['avatar', 'default', 'value' => Yii::$app->params['defaultAvatar']],
                ['cardId', 'formateCard'],
                ['cardNumber', 'validateCardNumber'],
                ['properties', 'ensureProperties'],
                ['properties', 'default', 'value' => []],
                ['tags', 'default', 'value' => []],
                ['tags', 'validateTags'],
                ['score', 'default', 'value' => 0],
                ['score', 'number', 'min'=>0, 'integerOnly'=>true],
                ['score', 'scoreMaxControl'],
                ['totalScore', 'default', 'value' => 0],
                ['unionId', 'validateUnionId'],
                ['socials', 'default', 'value' => []],
                ['origin', 'in', 'range' => self::$origins],
                ['qrcodeViewed', 'default', 'value' => false],
                ['isDisabled', 'default', 'value' => false],
                ['totalScoreAfterZeroed', 'default', 'value' => 0]
            ]
        );
    }

    public function validateCardNumber($attribute)
    {
        if ($attribute !== 'cardNumber') {
            return true;
        }
        $cardNumber = $this->$attribute;
        $member = Member::getByCardNumber($cardNumber);
        if (!empty($member) && $member->_id != $this->_id) {
            throw new ServerErrorHttpException(Yii::t('common', 'card_number_exists'));
        }
    }

    public function scoreMaxControl($attribute)
    {
        if ($attribute !== 'score') {
            return true;
        }
        $score = $this->$attribute;
        $this->$attribute = ($score > \Yii::$app->params['member_max_score']) ? \Yii::$app->params['member_max_score'] : intval($score);
    }

    public function validateTags($attribute)
    {
        if ($attribute !== 'tags') {
            return true;
        }
        $tags = empty($this->$attribute) ? [] : $this->$attribute;
        if (array_keys($tags) !== range(0, count($tags) - 1)) {
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }
    }

    public function validateUnionId($attribute)
    {
        if ($attribute !== 'unionId') {
            return true;
        }

        $model = self::getByUnionid($this->$attribute);

        if (!empty($model) && ($model->_id . '' !== $this->_id . '')) {
            $this->addError($attribute, $this->$attribute . " has been used.");
        }
    }

    public function ensureProperties($attribute)
    {
        //only validate the field "article"
        if ($attribute !== 'properties') {
            return true;
        }

        $properties = $this->$attribute;
        if (!is_array($properties)) {
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }

        $memberProperty = [];
        //validate each field in properties
        foreach ($properties as $property) {
            //validate the required fields in properties
            if (empty($property['id']) || empty($property['name'])) {
                throw new InvalidParameterException(Yii::t('common', 'data_error'));
            }

            // formate property id string to mongoId
            if (!empty($property['id']) && $property['name'] !== self::DEFAULT_PROPERTIES_MOBILE) {
                $property['id'] = new MongoId($property['id']);
                $memberProperty[] = $property;
            }

            if ($property['name'] == self::DEFAULT_PROPERTIES_BIRTHDAY) {
                //get month like 1, 2, 3 ... 12
                $month = date('n', TimeUtil::ms2sTime($property['value']));
                //get day like 1, 2, 3, ... 31
                $day = date('j', TimeUtil::ms2sTime($property['value']));
                $this->birth = $month * 100 + $day;
            }

            if ($property['name'] === self::DEFAULT_PROPERTIES_MOBILE) {
                $this->phone = $property['value'];
            }
        }

        $this->$attribute = $memberProperty;
    }

    /**
     * @return int
     * @param $birthday, int, Millisecond
     */
    public static function setMemberBirth($birthday)
    {
        $month = date('n', TimeUtil::ms2sTime($birthday));
        //get day like 1, 2, 3, ... 31
        $day = date('j', TimeUtil::ms2sTime($birthday));
        return $month * 100 + $day;
    }

    /**
     * Formate card info, cardId from string to mongoId, generate cardNumber
     * @param string $attribute
     */
    public function formateCard($attribute)
    {
        //only validate the field "cardId"
        if ($attribute !== 'cardId') {
            return true;
        }

        $this->$attribute = new \MongoId($this->$attribute);
        $this->cardProvideTime = new \MongoDate();
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into Member.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                // get card info
                'socials',
                'card' => function () {
                    $card = MemberShipCard::findByPk($this->cardId);
                    return $card;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                // get account info
                'socialAccount' => function () {
                    if (!empty($this->socialAccountId)) {
                        $channel = Channel::getByChannelId($this->socialAccountId, $this->accountId);
                    }
                    $result = [
                        'id' => $this->socialAccountId,
                        'origin' => $this->origin
                    ];
                    $result['name'] = empty($channel->name) ? '' : $channel->name;
                    $result['type'] = empty($channel->type) ? '' : $channel->type;
                    $result['status'] = empty($channel->status) ? '' : $channel->status;
                    return $result;
                },
                'socialMember' => function () {
                    if (!empty($this->openId) && !empty($this->socialAccountId)) {
                        try {
                            $socialMember = \Yii::$app->weConnect->getFollowerByOriginId($this->openId, $this->socialAccountId);
                        } catch (ApiDataException $e) {
                            $socialMember = '';
                        }
                    }

                    $result = null;
                    if (!empty($socialMember) && !empty($socialMember['nickname'])) {
                        $result = $socialMember['nickname'];
                    }
                    return $result;
                },
                // formate mongoId to string
                'properties' => function () {
                    $result = [];
                    foreach ($this->properties as $property) {
                        $property['id'] = $property['id'] . '';
                        $result[] = $property;
                    }
                    return $result;
                },
                'cardProvideTime' => function () {
                    return MongodbUtil::MongoDate2String($this->cardProvideTime, 'Y-m-d H:i:s');
                },
                'cardExpired' => function () {
                    if (!empty($this->cardExpiredAt) && $this->cardExpiredAt < TimeUtil::msTime()) {
                        return 1;
                    } else {
                        return 0;
                    }
                },
                'avatar', 'location', 'tags', 'score', 'remarks', 'cardNumber', 'unionId', 'totalScore',
                'cardExpiredAt' => function () {
                    return empty($this->cardExpiredAt) ? '' : TimeUtil::msTime2String($this->cardExpiredAt);
                },
                'birth', 'openId', 'qrcodeViewed', 'totalScoreAfterZeroed', 'isDisabled', 'phone'
            ]
        );
    }

    protected static function findByCondition($condition, $one)
    {
        $query = static::find();

        if (!empty($condition) && !ArrayHelper::isAssociative($condition)) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $condition = [$primaryKey[0] => $condition];
            } else {
                throw new InvalidConfigException(get_called_class() . ' must have a primary key.');
            }
        }
        //format condition about property tel
        self::normalizeConditionPhone($condition);
        $condition['isDeleted'] = self::NOT_DELETED;
        return $one ? $query->andWhere($condition)->one() : $query->andWhere($condition)->all();
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws \yii\base\InvalidParamException if relation name is wrong
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        $value = parent::__get($name);
        //append property 'tel' into member properties
        if ($name === 'properties') {
            $propertyNames = ArrayHelper::getColumn($value, 'name');
            if (!in_array(self::DEFAULT_PROPERTIES_MOBILE, $propertyNames)) {
                $propertyMobile = MemberProperty::getDefaultByName($this->accountId, self::DEFAULT_PROPERTIES_MOBILE);
                if (!empty($propertyMobile)) {
                    $value[] = [
                        'id' => $propertyMobile->_id,
                        'name' => self::DEFAULT_PROPERTIES_MOBILE,
                        'value' => $this->phone
                    ];
                }
            }
        }
        return $value;
    }

    private static function normalizeConditionPhone(&$condition)
    {
        foreach ($condition as $key => $value) {
            if ($key === 'properties' && isset($value['$elemMatch']['value'])) {
                if (isset($value['$elemMatch']['name']) && $value['$elemMatch']['name'] === self::DEFAULT_PROPERTIES_MOBILE) {
                    unset($condition['properties']);
                    $condition['phone'] = $value['$elemMatch']['value'];
                } else if (isset($value['$elemMatch']['id'])) {
                    $property = MemberProperty::findByPk($value['$elemMatch']['id']);
                    if (!empty($property) && $property->name === self::DEFAULT_PROPERTIES_MOBILE) {
                        unset($condition['properties']);
                        $condition['phone'] = $value['$elemMatch']['value'];
                        if (empty($condition['accountId'])) {
                            $condition['accountId'] = $property->accountId;
                        }
                    }
                }
            } else if (is_array($value)) {
                self::normalizeConditionPhone($condition[$key]);
            }
        }
    }

    /**
     * Get by card number
     * @param string
     * @return object|array
     * @author Rex Chen
     */
    public static function getByCardNumber($cardNumber)
    {
        return self::find()->where(['cardNumber' => $cardNumber])->one();
    }

    /**
     * get the card expired time for member
     */
    public static function getCardExpiredTime($params)
    {
        $cardStates = [];
        $cardStates = explode(',', $params['cardStates']);
        $cardExpiredAt = [];
        if (in_array(self::EXPIRED_IN_ONE_DAY, $cardStates)) {
            $cardExpiredAt = [
                'cardExpiredAt' => [
                    '$gt' => TimeUtil::msTime(),
                    '$lte' => TimeUtil::msTime(strtotime('+1 days', strtotime(date('Y-m-d')))),
                ]
            ];
        }
        if (in_array(self::EXPIRED_IN_ONE_WEEK, $cardStates)) {// expired in one week include expired in one day
            $expiredCondition = [
                'cardExpiredAt' => [
                    '$gt' => TimeUtil::msTime(),
                    '$lte' => TimeUtil::msTime(strtotime('+7 days', strtotime(date('Y-m-d'))))
                ]
            ];
            $cardExpiredAt = $expiredCondition;
        }
        if (in_array(self::EXPIRED, $cardStates)) {// condition or with expired in one week include
            $expiredCondition = [
                'cardExpiredAt' => [
                    '$lte' => TimeUtil::msTime(),
                    '$gt' => 0,
                ]
            ];
            if (!empty($cardExpiredAt)) {
                $cardExpiredAt = [
                    '$or' => [
                        $cardExpiredAt,
                        $expiredCondition
                    ]
                ];
            } else {
                $cardExpiredAt = $expiredCondition;
            }
        }
        return $cardExpiredAt;
    }

    public static function getCondition($params, $accountId)
    {
        $comma = ',';
        $condition = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        if (!empty($params['accounts'])) {
            $accounts = explode($comma, $params['accounts']);
            $ids = [];
            $origins = [];
            foreach ($accounts as $account) {
                if (self::MONGO_ID_LENGTH == strlen($account)) {
                    $ids[] = $account;
                } else {
                    $origins[] = $account;
                }
            }
            $channelCondition = [
                '$or' => [
                    [
                        'socialAccountId' => ['$in' => $ids],
                        'origin' => ['$in' => [self::WECHAT, self::WEIBO, self::ALIPAY]]
                    ],
                    [
                        'socials' => [
                            '$elemMatch' => [
                                'channel' => ['$in' => $ids],
                                'origin' => ['$in' => [self::WECHAT, self::WEIBO, self::ALIPAY]]
                            ]
                        ]
                    ]
                ]
            ];
            if (count($origins) > 0) {
                $channelCondition['$or'][] = [
                    'origin' => [
                        '$in' => $origins
                    ]
                ];
                $channelCondition['$or'][] = [
                    'socials.origin' => [
                        '$in' => $origins
                    ]
                ];
            }
            $condition = array_merge($condition, $channelCondition);
        }

        $cardStatusCondition = [];
        if (!empty($params['cardExpiredAt'])) {
            $cardStatusCondition = $params['cardExpiredAt'];
        }

        if (!empty($params['cards'])) {
            $cards = explode($comma, $params['cards']);
            $cardIds = [];
            foreach ($cards as $card) {
                $cardIds[] = new \MongoId($card);
            }
            $cards = ['$in' => $cardIds];
            $condition = array_merge($condition, ['cardId' => $cards]);
        }

        if (!empty($params['tags'])) {
            $tags = explode($comma, $params['tags']);
            $tags = ['$all' => $tags];
            $condition = array_merge($condition, ['tags' => $tags]);
        }

        $searchCondition = [];
        if (isset($params['searchKey']) && $params['searchKey'] != '') {
            $key = $params['searchKey'];
            //get the type of condition from search key
            $searchCondition = self::createCondition($key, $accountId);
        }

        // After run new MongoDate, The time can lost accuracy, so it will plus 1 or subtract 1.
        if (!empty($params['startTime'])) {
            $startTime = TimeUtil::ms2sTime($params['startTime']) - 1;
            $condition['createdAt']['$gt'] = new \MongoDate($startTime);
        }

        if (!empty($params['endTime'])) {
            $endTime = TimeUtil::ms2sTime($params['endTime']) + 1;
            $condition['createdAt']['$lt'] = new \MongoDate($endTime);
        }


        if (!empty($params['gender'])) {
            $gender = [
                'properties' => [
                    '$elemMatch' => [
                        'name' => self::DEFAULT_PROPERTIES_GENDER,
                        'value' => strtolower($params['gender'])
                    ]
                ]
            ];
            $condition = array_merge($condition, $gender);
        }
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                if ($key == 'country' || $key == 'province' || $key == 'city') {
                    $key = 'location.' . $key;
                    $condition = array_merge($condition, [$key => $value]);
                }
            }
        }

        //memberId
        if (!empty($params['memberId'])) {
            $memberIds = explode(',', $params['memberId']);
            $memberIds = MongodbUtil::toMongoIdList($memberIds);
            $condition = array_merge($condition, ['_id' => ['$in' => $memberIds]]);
        }
        return ['condition' => $condition, 'cardStatusCondition' => $cardStatusCondition, 'searchCondition' => $searchCondition];
    }

    /**
     * create condition base on the key
     * @param $key, string
     */
    public static function createCondition($key, $accountId)
    {
        $search = [];
        $key = str_replace('：', ':', $key);
        $position = strpos($key, ':');
        if (false === $position) {
            $key = StringUtil::regStrFormat(trim($key));
            $keyReg = new \MongoRegex("/$key/i");
            $search = [
                '$or' => [
                    ['cardNumber' => $keyReg],
                    [
                        'properties' =>
                            [
                                '$elemMatch' => [
                                    'name' => self::DEFAULT_PROPERTIES_NAME,
                                    'value' => $keyReg
                                ]
                            ]
                    ],
                    ['phone' => $keyReg]
                ]
            ];
        } else {
            $sourceName = trim(substr($key, 0, $position));
            $name = strtolower($sourceName);
            $sourceKey = substr($key, $position + 1);
            $key = StringUtil::regStrFormat(trim($sourceKey));
            $keyReg = new \MongoRegex("/$key/i");

            //change the property name from chinese to english
            $mapProperties = self::$mapProperties;
            if (isset($mapProperties[$name])) {
                $name = $mapProperties[$name];
            }

            if ($name == 'cardnumber') {
                $search =['cardNumber' => $keyReg];
            } else if (self::DEFAULT_PROPERTIES_BIRTHDAY == $name) {
                $search = [
                    'properties' => [
                        '$elemMatch' => [
                            'name' => $name,
                            'value' => self::converTimeValue($sourceKey)
                        ]
                    ]
                ];
            } else if (self::DEFAULT_PROPERTIES_MOBILE == $name) {
                $search =['phone' => $keyReg];
            } else if (self::DEFAULT_PROPERTIES_GENDER == $name) {
                $genderMap = self::$genderMap;
                $genderValue = isset($genderMap[$sourceKey]) ? $genderMap[$sourceKey] : $sourceKey;
                $search = [
                    'properties' => [
                        '$elemMatch' => [
                            'name' => $name,
                            'value' => $genderValue
                        ]
                    ]
                ];
            } else {
                //check the name whether to change,if change it means this attibute is defined in system
                $sourceName = (strtolower($sourceName) != $name) ? $name : $sourceName;
                $sourceName = StringUtil::regStrFormat(trim($sourceName));

                $type = self::getPropertyType($sourceName, $accountId);
                if ($type == MemberProperty::TYPE_DATE) {
                    $keyReg = self::converTimeValue($sourceKey);
                }

                $search = [
                    'properties' =>
                        [
                            '$elemMatch' => [
                                'name' => new \MongoRegex("/$sourceName/i"),
                                'value' => $keyReg
                            ]
                        ]
                ];
            }
        }

        return $search;
    }


    /**
     * conver the value for date
     */
    public static function converTimeValue($sourceKey)
    {
        //to surport to use dot
        if (strpos($sourceKey, '.')) {
            $sourceKey = str_replace('.', '-', $sourceKey);
        }
        if (!is_numeric($sourceKey) || count($sourceKey) < 10) {
            if (strstr($sourceKey, '/')) {
                $sourceKey = str_replace('/', '-', $sourceKey);
            }
            $sourceKey = TimeUtil::msTime(strtotime($sourceKey));
        }
        return $sourceKey;
    }

    /**
     * get the type of properties of member
     * @param $name,string,the property name
     * @param $accountId,MongoId
     */
    public static function getPropertyType($name, $accountId)
    {
        $search = [
            'name' => new \MongoRegex("/$name/i"),
            'accountId' => $accountId,
        ];
        $info = MemberProperty::findOne($search);
        if (!empty($info)) {
            return $info['type'];
        }
        return 'input';
    }


    /**
     * Search member by conditions like property, socialAccountId, cardId, tags, location, gender.
     * @param Array $params
     * @param string $accountId
     * @return member info
     */
    public static function search($params, $accountId)
    {
        $query = Member::find();

        $condition = self::getCondition($params, $accountId);

        $query->orderBy(self::normalizeOrderBy($params));
        $query->where($condition['condition']);
        if (!empty($condition['cardStatusCondition'])) {
            $query->andWhere($condition['cardStatusCondition']);
        }
        if (!empty($condition['searchCondition'])) {
            $query->andWhere($condition['searchCondition']);
        }
        return new ActiveDataProvider(['query' => $query]);
    }

    /**
     * Get member by tags
     * @param Array $tags
     * @param string $accountId
     * @return Array member info
     */
    public static function getByAccountAndTags($tags, $accountId)
    {
        $condition = ['tags' => ['$all' => $tags], 'accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        return Member::find()->where($condition)->all();
    }

    /**
     * Get the member count by tag name
     * @param String | Array $tagName
     * @return Integer
     **/
    public static function getMemberCountByTags($tags, $accountId)
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $condition = ['tags' => ['$in' => $tags], 'accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        return self::find()->where($condition)->count();
    }

    /**
     * This function is provide to generate card number.
     * Card number is a fixed length number. eg: 12000045
     * The first two digits of card bumber means type of card.
     * The rest digits means member'count.
     * @param mongoId $cardId
     * @return int cardnumber
     */
    public static function generateCardNumber()
    {
        $redis = Yii::$app->cache->redis;
        $cardNumber = $redis->get(self::MAX_CARD_NUMBER);
        if (empty($cardNumber)) {
            //Find the member order by cardNumber
            $member = Member::find()->orderBy(['cardNumber' => SORT_DESC])->one();
            if (empty($member) || empty($member['cardNumber'])) {
                $cardNumber = Yii::$app->params['card_number_count'] + 1;
            } else {
                $cardNumber = $member['cardNumber'] + 1;
            }
            $redis->set(self::MAX_CARD_NUMBER, intval($cardNumber));
        } else {
            $cardNumber = $redis->incr(self::MAX_CARD_NUMBER);
        }

        return (string) $cardNumber;
    }

    public static function getByNumbers($numbers)
    {
        return self::findAll(['cardNumber' => ['$in' => $numbers]]);
    }

    public static function getByNames($names)
    {
        $condition = [
            'properties' => [
                '$elemMatch' => [
                    'name' => self::DEFAULT_PROPERTIES_NAME,
                    'value' => ['$in' => $names]
                ]
            ]
        ];
        return self::findAll($condition);
    }

    public static function getByTags($tags)
    {
        $modifier = [];

        foreach ($tags as $tag) {
            $modifier[] = ['tags' => $tag];
        }

        $where = ['$or' => $modifier];
        return self::findAll($where);
    }

    /**
     * give the scores to the members by the ids
     * @param  int $score
     * @param  array<MongoId> $ids
     */
    public static function giveScoreByIds($score, $ids)
    {
        if ($score > 0) {
            Member::updateAll(['$inc' => ['score' => $score, 'totalScore' => $score, 'totalScoreAfterZeroed' => $score]], ['_id' => ['$in' => $ids]]);
        } else {
            $takeOffScore = 0 - $score;
            $updatedMemberIds = [];
            foreach ($ids as $id) {
                $condition = ['_id' => $id, 'score' => ['$gte' => $takeOffScore]];
                $updatedCount = Member::updateAll(['$inc' => ['score' => $score]], $condition);
                //if inc score fail or member score not enough, rollback and throw exception
                if ($updatedCount <= 0) {
                    Member::updateAll(['$inc' => ['score' => $takeOffScore]], ['_id' => ['$in' => $updatedMemberIds]]);
                    throw new InvalidParameterException(Yii::t('member', 'score_not_enough'));
                } else {
                    $updatedMemberIds[] = $id;
                }
            }
        }

        return $ids;
    }

    public static function giveScoreByNumbers($score, $numbers)
    {
        $members = self::getByNumbers($numbers);
        $memberIds = self::getIdList($members);

        if (self::giveScoreByIds($score, $memberIds)) {
            return $memberIds;
        }

        return false;
    }

    public static function giveScoreByNames($score, $names)
    {
        $members = self::getByNames($names);
        $memberIds = self::getIdList($members);

        if (self::giveScoreByIds($score, $memberIds)) {
            return $memberIds;
        }

        return false;
    }

    public static function giveScoreByTags($score, $tags)
    {
        $members = self::getByTags($tags);
        $memberIds = self::getIdList($members);

        if (self::giveScoreByIds($score, $memberIds)) {
            return $memberIds;
        }

        return false;
    }

    /**
     * Search member by card number limit 10
     * @param string $accountId
     * @param string $number
     * @return member info
     */
    public static function searchByCardNumber($accountId, $number)
    {
        $number = trim($number);
        $numberReg = new \MongoRegex("/$number/");
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => \backend\components\BaseModel::NOT_DELETED,
            'cardNumber' => $numberReg
        ];

        return Member::find()->andWhere($condition)->limit(10)->all();
    }

    /**
     * Search member by name limit 10
     * @param string $accountId
     * @param string $number
     * @return member info
     */
    public static function searchByName($accountId, $name)
    {
        $name = trim($name);
        $name = StringUtil::regStrFormat($name);
        $nameReg = new \MongoRegex("/($name)+/i");
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => \backend\components\BaseModel::NOT_DELETED,
            'properties' => [
                                '$elemMatch' => [
                                    'name' => self::DEFAULT_PROPERTIES_NAME,
                                    'value' => $nameReg
                                ]
                            ]
        ];

        return Member::find()->andWhere($condition)->limit(10)->all();
    }

    public static function searchByBirth($birthFrom, $birthTo, $accountId)
    {
        return self::findAll(['birth' => ['$gte' => $birthFrom, '$lte' => $birthTo], 'accountId' => $accountId]);
    }

    /**
     * Get by cardNumbers
     * @param array $cardNumber
     * @return member info
     */
    public static function getByCardNumbers($cardNumber)
    {
        return self::findAll(['cardNumber' => ['$in' => $cardNumber]]);
    }

    /**
     * Get by openId
     * @param string $openId
     * @return member info
     */
    public static function getByOpenId($openId)
    {
        $condition = [
            '$or' => [
                ['openId' => $openId],
                ['socials.openId' => $openId]
            ]
        ];
        return self::findOne($condition);
    }

    /**
     * Get by openIds
     */
    public static function getByOpenIds($accountId, $openIds)
    {
        $condition = [
            'accountId' => $accountId,
            '$or' => [
                ['openId' => ['$in' => $openIds]],
                ['socials.openId' => ['$in' => $openIds]]
            ]
        ];
        return self::findAll($condition);
    }

    public static function getByUnionid($unionId)
    {
        return self::findOne(['unionId' => $unionId]);
    }

    /**
     * Get by property
     * @param mongoId $propertyId
     * @param string $value
     * @return array member info
     */
    public static function getByProperty($propertyId, $value, $extralCondition = [])
    {
        $condition = [
            'properties' => [
                '$elemMatch' => [
                    'id' => $propertyId,
                    'value' => $value
                ]
            ]
        ];
        if (!empty($extralCondition)) {
            $condition = array_merge($condition, $extralCondition);
        }
        return self::findOne($condition);
    }

    public static function getByMobile($phone, $accountId = null)
    {
        $condition = [
            'properties' => [
                '$elemMatch' => [
                    'name' => self::DEFAULT_PROPERTIES_MOBILE,
                    'value' => $phone
                ]
            ]
        ];
        if (!empty($accountId)) {
            $condition = array_merge($condition, ['accountId' => $accountId]);
        }
        return self::findOne($condition);
    }

    /**
     * Get location statistics
     * @param string $location, country, province, city
     * @param string $parrent
     * @param mongoId $accountId
     * @param mongoDate $createdAt
     * @return Ambigous <multitype:, boolean>
     */
    public static function getLocations($location, $parrent, $accountId, $createdAt = null)
    {
        $condition = ['accountId' => $accountId];
        if ($location == 'province') {
            $condition = array_merge($condition, ['location.country' => $parrent]);
        } else if ($location == 'city') {
            $condition = array_merge($condition, ['location.province' => $parrent]);
        }

        if (!empty($createdAt)) {
            $condition = array_merge($condition, ['createdAt' => ['$gte' => $createdAt]]);
        }

        return self::distinct("location.$location", $condition);
    }

    public static function getCountByCardId($cardId)
    {
        return self::count(['cardId' => $cardId]);
    }

    public static function rewardByScoreRule($ruleName, $memberId, $accountId)
    {
        //get score rule
        $rule = ScoreRule::getByName($ruleName, $accountId);

        if (!empty($rule) && $rule->isEnabled) {
            //if check the property, the property is not filled,not need to give reward
            if (!self::_checkRulePropertiesFilled($rule, $memberId)) {
                return true;
            }
            //get reward type
            if ($rule->rewardType == ScoreRule::REWARD_SCORE_TYPE) {
                // member rewarded
                $rewardHistory = ScoreHistory::getByRuleName($ruleName, $memberId, $accountId);
                if (!empty($rewardHistory)) {
                    return true;
                }
                //reward score
                $memberList = Member::giveScoreByIds($rule->score, [$memberId]);

                //update history
                $scoreHistory = new ScoreHistory();
                $scoreHistory->assigner = ScoreHistory::ASSIGNER_RULE;
                $scoreHistory->increment = $rule->score + 0;
                $scoreHistory->memberId = $memberId;
                $scoreHistory->brief = ScoreHistory::ASSIGNER_RULE;
                $scoreHistory->description = $ruleName;
                $scoreHistory->channel = ['origin' => ScoreHistory::PORTAL];
                $scoreHistory->accountId = $accountId;
                $scoreHistory->save();
            } else if ($rule->rewardType == ScoreRule::REWARD_COUPON_TYPE && !empty($rule->couponId)) {
                $rewardCouponLog = CouponLog::getLogByRuleName($ruleName, $memberId, $accountId);
                if (!empty($rewardCouponLog)) {
                    return true;
                }
                $coupon = Coupon::findByPk($rule->couponId);
                if (empty($coupon)) {
                    LogUtil::error(['message' => 'can not find the coupon when give member birthday reward', 'couponId' => (string)$couponId], 'member');
                    return true;
                }

                $member = Member::findByPk($memberId);
                if (Coupon::updateAll(['$inc' => ['total' => -1]], ['total' => ['$gt' => 0], '_id' => $rule->couponId])) {
                    //issue membershipdiscount
                    $membershipDiscount = MembershipDiscount::transformMembershipDiscount($coupon, $member, $ruleName);
                    if (false == $membershipDiscount->save()) {
                        Coupon::updateAll(['$inc' => ['total' => 1]], ['_id' => $rule->couponId]);
                        LogUtil::error(['message' => 'add membershipdiscount fail', 'memberId' => (string)$member->_id, 'couponId' => (string)$coupon->_id], 'member');
                    }
                } else {
                    LogUtil::error(['message' => 'coupon is not enough when give member birthday reward', 'memberId' => (string)$member->_id, 'couponId' => (string)$coupon->_id], 'member');
                }
            }
        }
    }

    private function _checkRulePropertiesFilled($scoreRule, $memberId)
    {
        if ($scoreRule->name == ScoreRule::NAME_PERFECT_INFO) {
            if (empty($scoreRule->properties)) {
                return false;
            }
            $member = Member::findByPk($memberId);
            if (!empty($member->properties)) {
                $ruleProperties = $scoreRule->properties;
                $memberProperties = $member->properties;
                $filledPropertyIds = [];
                foreach ($memberProperties as $memberProperty) {
                    if (isset($memberProperty['value']) && $memberProperty['value'] != [] && $memberProperty['value'] != '') {
                        $filledPropertyIds[] = (string) $memberProperty['id'];
                    }
                }
                foreach ($ruleProperties as $rulePropertyId) {
                    if (!in_array((string) $rulePropertyId, $filledPropertyIds)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function upgradeCard()
    {
        $currentCard = MemberShipCard::findByPk($this->cardId);
        if (isset($currentCard->isAutoUpgrade) && $currentCard->isAutoUpgrade) {
            // get the right card according member score.
            $card = MemberShipCard::getSuitableCard($this->totalScore, $this->accountId);
            $cardCondition = $card->condition;

            $currentCardCondition = $currentCard->condition;
            // if member's card is not right, update member card
            if (!empty($card->_id) && (string) $this->cardId != (string) $card->_id &&
                $cardCondition['minScore'] > $currentCardCondition['minScore']) {
                $this->cardId = $card->_id;
                $this->save(false, ['cardId']);
            }
        }
    }

    /**
     * get value from member
     * @param $memberId, mongoId
     * @param $name, string or array
     */
    public static function getMemberInfo($memberId, $name = 'name')
    {
        $member = Member::findByPk($memberId);
        $result = [];
        if (is_string($name)) {
            $name = explode(',', $name);
        }

        if (!empty($member->properties)) {
            foreach ($member->properties as $property) {
                foreach ($name as $value) {
                    if ($value == $property['name']) {
                        $result[$value] = $property['value'];
                    }
                    if (empty($result[$value])) {
                        $result[$value] = '';
                    }
                }
            }
        } else {
            foreach ($name as $value) {
                $result[$value] = '';
            }
        }
        return $result;
    }

    /**
     * @return array, memberId => propertyValue
     * @param $member, object
     * @param $propertyName
     */
    public static function getMemberIdMatchProperty($member, $propertyName)
    {
        $memberId = (string)$member->_id;
        if (!empty($member->properties)) {
            foreach ($member->properties as $property) {
                if ($property['name'] == $propertyName) {
                    return [$memberId => $property['value']];
                }
            }
        }
        return [$memberId => ''];
    }

    /**
     * Auto zeroed member score
     */
    public function resetScore()
    {
        $memberScore = $this->score;
        $this->score = 0;
        $this->totalScoreAfterZeroed = 0;
        $updateResult = $this->update(true, ['score', 'totalScoreAfterZeroed']);

        if ($updateResult && $memberScore) {
            $scoreHistory = new ScoreHistory();
            $scoreHistory->assigner = ScoreHistory::ASSIGNER_AUTO_ZEROED;
            $scoreHistory->increment = -$memberScore;
            $scoreHistory->memberId = $this->_id;
            $scoreHistory->brief = ScoreHistory::ASSIGNER_AUTO_ZEROED;
            $scoreHistory->channel = ['origin' => ScoreHistory::PORTAL];
            $scoreHistory->accountId = $this->accountId;
            $saveResult = $scoreHistory->save();

            if (!$saveResult) {
                LogUtil::error(['message' => 'Save score history fail', 'errors' => $scoreHistory->getErrors()], 'member');
            }
        } else if ($updateResult === false) {
            LogUtil::error(['message' => 'Reset score fail', 'errors' => $this->getErrors(), 'memberId' => $this->_id], 'member');
        }
    }

    /**
     * deal with the data before export
     * @param $member,object
     * @param $args,array
     */
    public static function preProcessMemberData($members, $args)
    {
        $cardMap = $args['cardMap'];
        $socialAccountsMap = $args['socialAccountsMap'];
        $memberProperties = $args['memberProperties'];

        $memberData = [];

        foreach ($memberProperties as $memberProperty) {
            $memberData[(string)$memberProperty['_id']] = $memberProperty['type'];
        }

        $rows = [];
        foreach ($members as $member) {
            $memberTag = '';
            if (!empty($member->tags)) {
                $memberTag = implode(',', $member->tags);
            }
            $row = [
                'tag' => $memberTag,
                'cardNumber' => $member->cardNumber,
                'cardName' => empty($cardMap[(string) $member->cardId]) ? '' : $cardMap[(string) $member->cardId],
                'score' => $member->score,
                'totalScore' => $member->totalScore,
                'totalScoreAfterZeroed' => intval($member->totalScoreAfterZeroed),
                'costScoreAfterZeroed' => intval($member->totalScoreAfterZeroed) - $member->score,
                'channel' => empty($socialAccountsMap[(string) $member->socialAccountId]) ? Yii::t('common', $member->origin) : $socialAccountsMap[(string) $member->socialAccountId],
                'createdAt' => MongodbUtil::MongoDate2String($member->createdAt),
            ];

            foreach ($member->properties as $property) {
                if (isset($memberData[(string)$property['id']]) && $memberData[(string)$property['id']] == MemberProperty::TYPE_DATE) {
                    $dateFormate = ($property['name'] == 'birthday') ? 'Y-m-d' : 'Y-m-d H:i:s';
                    $row[$property['name']] = empty($property['value']) ? '' : TimeUtil::msTime2String($property['value'], $dateFormate);
                } else if (is_array($property['value'])) {
                    $row[$property['name']] = implode($property['value'], ',');
                } else {
                    if ($property['name'] == self::DEFAULT_PROPERTIES_GENDER) {
                        $property['value'] = Yii::t('member', $property['value']);
                    }
                    $row[$property['name']] = $property['value'];
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * deal with the klp data before export
     * @param $member,object
     * @param $headerKey,array
     */
    public static function preProcessKlpMemberData($member, $headerKeys)
    {
        $row = [];
        $row['tel_1'] = $row['tel_2'] = empty($member['phone']) ? '' : $member['phone'];
        foreach ($member['properties'] as $property) {
            foreach ($headerKeys as &$headerKey) {
                //set the default value for County and Country
                $row['county'] = $row['country'] = '台灣';
                //change the value for gender
                if ($property['name'] == self::DEFAULT_PROPERTIES_GENDER) {
                    if ($property['value'] == 'male') {
                        $property['value'] = '先生';
                    } else {
                        $property['value'] = '小姐';
                    }
                }
                //modify the name,divide the name,and the result is firstName and surname
                if ($property['name'] == self::DEFAULT_PROPERTIES_NAME) {
                    list($lastName, $firstName) = StringUtil::splitName($property['value']);
                    $row['firstName'] = $firstName;
                    $row['lastName'] = $lastName;
                }
                if ($property['name'] == $headerKey) {
                    if (is_array($property['value'])) {
                        $row[$headerKey] = implode(',', $property['value']);
                    } else {
                        $row[$headerKey] = $property['value'];
                    }
                }
                //set ''
                if ($headerKey == 'tel' || $headerKey == '密碼') {
                    $row[$headerKey] = '';
                }

                //set default value;if the key value is not set,we must set the default value
                if (!isset($row[$headerKey])) {
                    $row[$headerKey] = '';
                }
            }
        }
        unset($member, $headerKeys);
        return $row;
    }

    public static function getNewMemberStats($start, $end)
    {
        $pipeline = [
            ['$match' => ['createdAt' => ['$gte' => $start, '$lt' => $end], 'isDeleted' => self::NOT_DELETED]],
            [
                '$project' => [
                    'socialAccountId' => ['$ifNull' => ['$socialAccountId', '']],
                    'origin' => '$origin',
                    'accountId' => '$accountId'
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'origin' => '$origin', 'socialAccountId' => '$socialAccountId', 'accountId' => '$accountId'
                    ],
                    'total' => ['$sum' => 1]
                ]
            ]
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    public static function countByAccount($accountId, $startTime = null, $endTime = null)
    {
        $condition = ['accountId' => $accountId];
        if ($startTime !== null) {
            $condition['createdAt']['$gte'] = $startTime;
        }
        if ($endTime !== null) {
            $condition['createdAt']['$lt'] = $endTime;
        }
        return self::count($condition);
    }

    public static function getNewMemberIds($accountId, $start, $end)
    {
        $condition = ['accountId' => $accountId, 'createdAt' => ['$gte' => $start, '$lt' => $end]];
        return self::distinct('_id', $condition);
    }

    public static function getTagStats($accountId, $tags, $channels)
    {
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => self::NOT_DELETED,
            '$or' => [
                [
                    'socialAccountId' => ['$in' => $channels],
                    'origin' => ['$in' => [self::WECHAT, self::WEIBO, self::ALIPAY]]
                ],
                [
                    'origin' => [
                        '$in' => [
                            self::PORTAL,
                            self::APP_ANDROID,
                            self::APP_IOS,
                            self::APP_WEB,
                            self::APP_WEBVIEW,
                            self::OTHERS
                        ]
                    ]
                ]
            ]
        ];
        $pipeline = [
            ['$match' => $condition],
            ['$project' => ['tag' => '$tags']],
            ['$unwind' => '$tag'],
            ['$match' => ['tag' => ['$in' => $tags]]],
            ['$group' => ['_id' => '$tag', 'count' => ['$sum' => 1]]]
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    public static function renameTag($accountId, $name, $newName)
    {
        //add new tag to member
        Member::updateAll(['$addToSet' => ['tags' => $newName]], ['accountId' => $accountId, 'tags' => $name]);
        //remove old tags from member
        Member::updateAll(['$pull' => ['tags' => $name]], ['accountId' => $accountId, 'tags' => $name]);
    }

    public static function getWechatInfo($memberId)
    {
        $where = [
            '_id' => $memberId,
            'origin' => self::WECHAT,
        ];
        $memberInfo = Member::findOne($where);
        $channel = $openId = '';
        if (!empty($memberInfo) && !empty($memberInfo->socialAccountId)) {
            $channel = $memberInfo->socialAccountId;
            $openId = $memberInfo->openId;
        }
        return ['channel' => $channel, 'openId' => $openId];
    }

    /**
     * get member's property map [id => value]
     * @return array
     */
    public function getPropertyMap()
    {
        $propertyMap = [];
        $properties = $this->properties;
        foreach ($properties as $property) {
            $propertyMap[(string) $property['id']] = $property['value'];
        }

        return $propertyMap;
    }

    /**
     * validate member properties
     */
    public static function validateProperty($member)
    {
        $idValueMap = $member->getPropertyMap();

        //get all property
        $memberProperties = MemberProperty::getByAccount($member->accountId);

        foreach ($memberProperties as $memberProperty) {
            $id = $memberProperty->_id . '';
            //validate require
            if ($memberProperty->isRequired && !isset($idValueMap[$id])) {
                throw new InvalidParameterException([$id => Yii::t('member', 'require_filed')]);
            }

            //validate unique
            if ($memberProperty->isUnique && isset($idValueMap[$id]) && $idValueMap[$id] != "") {
                $extralCondition = ['_id' => ['$ne' => $member->_id]];
                $uniqueMember = Member::getByProperty($memberProperty->_id, $idValueMap[$id], $extralCondition);
                $condition = [
                    'properties' => [
                        '$elemMatch' => [
                            'id' => $memberProperty->_id,
                            'value' => $idValueMap[$id]
                        ]
                    ],
                    'accountId' => $member->accountId,
                ];
                if (!empty($uniqueMember) || !empty(Follower::findOne($condition))) {
                    if ($memberProperty->name == 'tel') {
                        throw new InvalidParameterException([$id => Yii::t('member', 'unique_tel_filed')]);
                    } else {
                        throw new InvalidParameterException([$id => Yii::t('member', 'unique_filed')]);
                    }
                }
            }

            //validate email
            if ($memberProperty->name === Member::DEFAULT_PROPERTIES_EMAIL && !empty($idValueMap[$id]) && !StringUtil::isEmail($idValueMap[$id])) {
                throw new InvalidParameterException([$id => Yii::t('member', 'email_format_error')]);
            }
        }
    }

    /**
     * Get member's default property
     * @param string $propertyName
     * @return string
     */
    public function getDefaultProperty($propertyName)
    {
        foreach ($this->properties as $property) {
            if ($property['name'] === $propertyName) {
                return $property['value'];
            }
        }
        return '';
    }

    /**
     * Get member channels
     * @return array
     */
    public function getChannels($memberId)
    {
        $channelItem = $channels = [];
        $memberSocials = $this->socialAccountId;
        $openIdItem = $this->openId;

        if (!empty($memberSocials) && !empty($openIdItem)) {
            $channel = Channel::findOne(['channelId' => $memberSocials]);
            $channelItem = array_merge($channel->toArray(), ["openId" => $openIdItem, "memberId" => $memberId]);
            $channels[] = $channelItem;
        }

        if (!empty($this->socials) && count($this->socials) > 0) {
            foreach ($this->socials as $social) {
                if (!empty($social['origin']) && in_array($social['origin'], [self::WECHAT, self::WEIBO, self::ALIPAY])) {
                    $openId = $social['openId'];
                    $channel = Channel::findOne(['channelId' => $social['channel']]);

                    if (!empty($channel)) {
                        $channelItem = array_merge($channel->toArray(), ["openId" => $openId, "memberId" => $memberId]);
                        $channels[] = $channelItem;
                    }
                }
            }
        }
        return $channels;
    }

    /**
     * get member info even if the member is deleted
     * @param $condition, array
     */
    public static function getAllMember($condition)
    {
        $members = Member::find()->where($condition)->all();
        return $members;
    }

    public static function webhookEvent($member)
    {
        $property = MemberProperty::findOne(['name' => Member::DEFAULT_PROPERTIES_MOBILE, 'accountId' => $member->accountId]);
        $phone = '';
        if (!empty($property) && !empty($member->properties)) {
            foreach ($member->properties as $memberProperty) {
                if ((string) $memberProperty['id'] === (string) $property->_id) {
                    $phone = $memberProperty['value'];
                    break;
                }
            }
        }

        $triggerData = [
            'type' => Webhook::EVENT_MEMBER_CREATED,
            'account_id' => (string) $member->accountId,
            'member_id' => (string) $member->_id,
            'phone' => $phone,
            'origin' => Member::PORTAL,
            'created_at' => MongodbUtil::MongoDate2String($member->createdAt, \DateTime::ATOM),
        ];
        Yii::$app->webhook->triggerEvent($triggerData);
    }

    /**
     * Give birthday score if member's birth day is today
     * @param object<Member> $member
     */
    public static function birthdayReward($member)
    {
        $memberId = $member->_id;
        $accountId = $member->accountId;

        $birthdayArgs = ['memberId' => $memberId . '', 'accountId' => $accountId . '', 'description' => 'Direct: Issue birthday score to member ' . $memberId];
        $birthdayResult = Yii::$app->job->create('backend\modules\member\job\Birthday', $birthdayArgs);
    }

    public static function findMembersByValues($batchValue, $propertyId, $accountId)
    {
        $condition = [
            'properties' => [
                '$elemMatch' => [
                    'id' => $propertyId,
                    'value' => ['$in' => $batchValue]
                ]
            ],
            'accountId' => $accountId
        ];
        return self::findAll($condition);
    }

    /**
     * Validate whether the property is required or unique.
     * @param $value string
     * @param $openId string
     * @param $memberProperty Object
     * @param $accountId MongoId
     *
     * @return boolean false|true
     */
    public static function validateRequiredAndUnique($value, $openId, $memberProperty, $accountId)
    {
        if ($memberProperty->isRequired == true && empty($value)) {
            throw new InvalidParameterException(Yii::t('member', 'member_is_required'));
        }

        if (!empty($value) && $memberProperty->isUnique == true) {
            $condition = [
                'properties' => [
                    '$elemMatch' => [
                        'id' => new MongoId($memberProperty->_id),
                        'value' => $value
                    ]
                ],
                'accountId' => $accountId,
            ];
            $member = self::findOne($condition);
            $followerCondition = $condition;
            $followerCondition['openId'] = ['$ne' => $openId];
            //check property not in colection member and not the same member
            //check property not in colection follower
            if ((!empty($member) && (empty($member->getSocials('openId')) || !in_array($openId, $member->getSocials('openId')))) ||
                !empty(Follower::findOne($followerCondition))) {
                throw new InvalidParameterException(Yii::t('member', 'unique_filed'));
            }
        }
        return true;
    }

    /**
     * Get socials
     * @param string $key, openId, channel
     * @return multitype:
     */
    public function getSocials($key)
    {
        if (empty($this)) {
            return [];
        }
        $result = ArrayHelper::getColumn($this->socials, $key);
        if ($key === 'channelId') {
            !empty($this->socialAccountId) && $result[] = $this->socialAccountId;
        } else {
            !empty($this->$key) && $result[] = $this->$key;
        }
        return $result;
    }

    /**
     * Member recive coupon
     * @param Coupon $coupon
     * @param string $reciveType
     * @throws ServerErrorHttpException
     * @return boolean
     */
    public function reciveCoupon($coupon, $reciveType)
    {
        $recivedTimes = CouponLog::countMemberReciveByCouponId($this->accountId, $this->_id, $coupon->_id);
        if ($recivedTimes >= $coupon->limit) {
            return false;
        }

        if (Coupon::updateAll(['$inc' => ['total' => -1]], ['total' => ['$gt' => 0], '_id' => $coupon->_id])) {
            //issue membershipdiscount
            $membershipDiscount = MembershipDiscount::transformMembershipDiscount($coupon, $this, $reciveType);
            if (!($membershipDiscount->save())) {
                Coupon::updateAll(['$inc' => ['total' => 1]], ['_id' => $coupon->_id]);
                LogUtil::error(['message' => 'Score rule reward fail: new membershipdiscount fail', 'memberId' => (string)$member->_id, 'couponId' => (string)$coupon->_id], 'member');
                throw new ServerErrorHttpException(Yii::t('member', 'reward_failed'));
            }
        } else {
            return false;
        }

        return true;
    }
}
