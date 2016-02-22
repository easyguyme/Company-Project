<?php
namespace backend\modules\member\models;

use Yii;
use MongoId;
use MongoDate;
use yii\web\ServerErrorHttpException;
use backend\utils\TimeUtil;
use backend\utils\MongodbUtil;
use backend\components\BaseModel;
use backend\components\ActiveDataProvider;
use backend\exceptions\InvalidParameterException;
use backend\modules\product\models\CouponLog;
use backend\utils\StringUtil;
use backend\modules\product\models\Coupon;
use yii\web\BadRequestHttpException;
use backend\modules\product\models\MembershipDiscount;
use backend\utils\LogUtil;

/**
 * Model class for account.
 *
 * The followings are the available columns in collection 'account':
 * @property MongoId    $_id
 * @property String     $name
 * @property int        $score
 * @property String     $triggerTime
 * @property String     $description
 * @property MongoTime  $startTime
 * @property MongoTime  $endTime
 * @property Boolean    $isEnabled
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property ObjectId   $accountId
 * @property int        $times
 * @property int        $memberCount
 * @property Array      $properties
 *
 * @author Devin.Jin
 **/

class ScoreRule extends BaseModel
{
    const REWARD_COUPON_TYPE = 'coupon';
    const REWARD_SCORE_TYPE = 'score';

    //constant for triggerTime
    const TRIGGER_TIME_DAY = "day";
    const TRIGGER_TIME_WEEK = "week";
    const TRIGGER_TIME_MONTH = "month";

    //constant for name
    const NAME_PERFECT_INFO = 'perfect_information';
    const NAME_BIRTHDAY = 'birthday';
    const NAME_FIRST_CARD = 'first_card';

    //const for limit type
    const UNLIMITED = 'unlimited';
    const LIMIT_DAY = 'day';

    /**
     * Declares the name of the Mongo collection associated with Member.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'scoreRule';
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
                'name', 'times', 'memberCount', 'score',
                'triggerTime', 'description', 'properties', 'startTime',
                'endTime', 'isEnabled', 'couponId', 'rewardType',
                'code', 'limit', 'isDefault',
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'name', 'times', 'memberCount',
                'score', 'triggerTime', 'description','properties',
                'startTime', 'endTime', 'isEnabled', 'couponId', 'rewardType',
                'code', 'limit', 'isDefault',
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
                [['name'], 'required'],
                ['name', 'string'],
                ['name', 'validateName'],
                ['score', 'integer'],
                ['score', 'default', 'value' => 0],
                ['couponId', 'default', 'value' => ''],
                ['couponId', 'toMongoId'],
                ['rewardType', 'default', 'value' => self::REWARD_SCORE_TYPE],
                ['rewardType', 'in', 'range' => [self::REWARD_SCORE_TYPE, self::REWARD_COUPON_TYPE]],
                ['description', 'default', 'value' => ''],
                ['triggerTime', 'in', 'range' => [self::TRIGGER_TIME_DAY, self::TRIGGER_TIME_WEEK, self::TRIGGER_TIME_MONTH]],
                ['triggerTime', 'default', 'value' => self::TRIGGER_TIME_DAY],
                ['description', 'string'],
                ['isEnabled', 'boolean'],
                ['isEnabled', 'default', 'value' => false],
                ['times', 'default', 'value' => 0],
                ['memberCount', 'default', 'value' => 0],
                ['properties', 'ensureMongoId'],
                ['properties', 'validateProperties'],
                ['startTime', 'conver2MongoDate'],
                ['endTime', 'conver2MongoDate'],
                ['code', 'validateCode'],
                ['limit', 'validateLimit'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into Member.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'score', 'triggerTime', 'description', 'isEnabled', 'times', 'memberCount',
                'code', 'limit', 'isDefault',
                'couponId' => function () {
                    return (string)$this->couponId;
                },
                'rewardType',
                'properties' => function () {
                    $properties = [];
                    if (!empty($this->properties)) {
                        foreach ($this->properties as $property) {
                            $properties[] = (string) $property;
                        }
                    }
                    return $properties;
                },
                'startTime' => function () {
                    return MongodbUtil::MongoDate2String($this->startTime);
                },
                'endTime' => function () {
                    return MongodbUtil::MongoDate2String($this->endTime);
                }
            ]
        );
    }

    public function conver2MongoDate($attribute)
    {
        if ($attribute == 'startTime' || $attribute == 'endTime') {
            $time = $this->$attribute;
            if (!empty($time) && (is_numeric($time))) {
                $this->$attribute = new MongoDate(TimeUtil::ms2sTime($time));
            }
        } else {
            return true;
        }
    }

    public function ensureMongoId($attribute)
    {
        if ($attribute != 'properties') {
            return true;
        }
        $properties = $this->$attribute;

        foreach ($properties as &$property) {
            $property = new MongoId($property);
        }
        $this->$attribute = $properties;
    }

    public function validateProperties($attribute)
    {
        if ($attribute != 'properties' || $this->name != self::NAME_PERFECT_INFO) {
            return true;
        }
        $properties = $this->$attribute;
        if (empty($properties)) {
            throw new InvalidParameterException(['rulePropertiesRequired' => Yii::t('common', 'required_filed')]);
        }
        $visablePropertyCount = MemberProperty::count(['_id' => ['$in' => $properties], 'isVisible' => true]);
        if ($visablePropertyCount != count($properties)) {
            throw new InvalidParameterException(['rulePropertiesRequired' => Yii::t('member', 'rule_property_error')]);
        }
    }

    public function validateLimit($attribute)
    {
        if ($attribute !== 'limit' || $this->isDefault) {
            return true;
        }

        $limit = $this->$attribute;
        if (empty($limit['type']) || !in_array($limit['type'], [self::UNLIMITED, self::LIMIT_DAY])) {
            throw new InvalidParameterException(['limit' => Yii::t('common', 'data_error')]);
        }
        $limit['value'] = (empty($limit['value']) || $limit['type'] === self::UNLIMITED) ? 0 : intval($limit['value']);
        $this->$attribute = $limit;
    }

    public function validateCode($attribute)
    {
        if ($attribute !== 'code' || $this->isDefault) {
            return true;
        }
        $code = $this->$attribute;
        $condition = ['code' => $code, 'isDefault' => false, 'accountId' => $this->accountId, '_id' => ['$ne' => $this->_id]];
        $existsScoreRule = self::findOne($condition);
        if (!empty($existsScoreRule)) {
            throw new InvalidParameterException(['code' => Yii::t('common', 'data_exists')]);
        }
    }

    public function validateName($attribute)
    {
        if ($attribute !== 'name') {
            return true;
        }
        $name = $this->$attribute;
        $defaultRuleNames = [self::NAME_BIRTHDAY, self::NAME_FIRST_CARD, self::NAME_PERFECT_INFO];
        if (($this->isDefault && !in_array($name, $defaultRuleNames)) || (!($this->isDefault) && in_array($name, $defaultRuleNames))) {
            throw new InvalidParameterException(['name' => Yii::t('member', 'invalid_score_rule_name')]);
        }
    }

    /**
     * Get by name and account
     * @param string $name
     * @param string $accountId
     * @return array score rule info
     */
    public static function getByName($name, $accountId)
    {
        return self::findOne(['name' => $name, 'accountId' => $accountId]);
    }

    /**
     * Search by condtions
     * @param string $accountId
     * @param array $condtion
     */
    public static function search($accountId, $condtion)
    {
        $query = Self::find();
        $condtions = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];

        if (is_array($condtion)) {
            $condtions = array_merge($condtions, $condtion);
        }

        $query->where($condtions);
        return new ActiveDataProvider(['query' => $query]);
    }

    public static function getByAccount($accountId)
    {
        return self::findAll(['accountId' => $accountId]);
    }

    public static function generateCode($accountId)
    {
        //get random string, 10 means length, 3 means lower letters
        $code = StringUtil::rndString(10, 3);
        $code = strtoupper($code);
        $scoreRule = self::getByCode($accountId, $code);
        if (empty($scoreRule)) {
            return $code;
        } else {
            return self::generateCode($accountId);
        }
    }

    public static function getByCode($accountId, $code)
    {
        return self::findOne(['accountId' => $accountId, 'code' => $code, 'isDefault' => false]);
    }

    /**
     * Reward by code
     * @param MongoId $accountId
     * @param Member $member
     * @param string $code
     * @param array $channel
     * @throws ServerErrorHttpException reward failed
     * @throws BadRequestHttpException can nor reward, example: coupon not found
     * @return boolean true: reward success, false: reward failed because of limit
     */
    public static function rewardByCode($accountId, $member, $code, $channel)
    {
        $scoreRule = ScoreRule::getByCode($accountId, $code);
        if (empty($scoreRule)) {
            throw new BadRequestHttpException(Yii::t('member', 'score_rule_not_found'));
        }
        if (!($scoreRule->isEnabled)) {
            return false;
        }

        //check reward limit
        $keyForMemberRewardTimes = '';
        $limit = $scoreRule->limit;
        if ($limit['type'] === self::LIMIT_DAY) {
            $limitTimes = $limit['value'];
            $redis = Yii::$app->cache->redis;
            $keyForMemberRewardTimes = date('Y-m-d') . '-' . $member->_id . '-' . $scoreRule->_id . '-scorerule-reward-times';
            $memberRewardTimes = $redis->incr($keyForMemberRewardTimes);
            //expire at next day
            $redis->expireat($keyForMemberRewardTimes, strtotime(date('Y-m-d') . ' +1 day'));
            if ($memberRewardTimes > $limitTimes) {
                return false;
            }
        }

        if ($scoreRule->rewardType === self::REWARD_SCORE_TYPE) {
            if (Member::giveScoreByIds($scoreRule->score, [$member->_id])) {
                return ScoreHistory::recordByScoreRule($scoreRule, $member->_id, $channel);
            } else {
                throw new ServerErrorHttpException(Yii::t('member', 'reward_failed'));
            }
        } else {
            $coupon = Coupon::findByPk($scoreRule->couponId);
            if (empty($coupon)) {
                throw new BadRequestHttpException(Yii::t('product', 'invalid_couponId'));
            }
            $reciveType = (string) $scoreRule->_id;
            $result = $member->reciveCoupon($coupon, $reciveType);
            //revert member reward times, if recive coupon failed
            if (!$result && !empty($keyForMemberRewardTimes)) {
                $redis->decr($keyForMemberRewardTimes);
            }
            return $result;
        }
    }
}
