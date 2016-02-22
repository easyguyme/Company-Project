<?php
namespace backend\modules\member\models;

use MongoDate;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\components\PlainModel;
use backend\components\ActiveDataProvider;

/**
 * Class file for model ScoreHistory
 *
 * The followings are the available columns in collection 'account':
 * @property MongoId   $_id
 * @property String    $assigner, "admin" or "rule"
 * @property Integer   $increment
 * @property string    $brief
 * @property String    $description
 * @property ObjectId  $memberId
 * @property MongoDate $createdAt
 * @property ObjectId  $accountId
 *
 * @author Devin.Jin
 **/
class ScoreHistory extends PlainModel
{
    //constants for assigner
    const ASSIGNER_ADMIN = "admin";
    //const for biref
    const ASSIGNER_RULE = "rule_assignee";
    const ASSIGNER_AUTO_ZEROED = "auto_zeroed";
    const ASSIGNER_EXCHAGE_GOODS = 'exchange_goods';
    const ASSIGNER_EXCHANGE_PROMOTION_CODE = 'exchange_promotion_code';
    const ASSIGNER_ADMIN_ISSUE_SCORE = 'admin_issue_score';
    const ASSIGNER_ADMIN_DEDUCT_SCORE = 'admin_deduct_score';
    const ASSIGNER_REWARD_SCORE = 'reward_score';
    const ASSIGNER_SHAKE_SCORE = 'shake_score';

    /**
     * Declares the name of the Mongo collection associated with Member.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'scoreHistory';
    }

    /**
     * Returns the list of all attribute names of member.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'createdAt'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['assigner', 'increment', 'brief', 'description', 'memberId', 'channel', 'user']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['assigner', 'increment', 'brief', 'description', 'memberId', 'channel', 'user']
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
                ['increment', 'integer'],
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
                'assigner', 'increment', 'brief', 'description', 'channel',
                'user' => function () {
                    $user = $this->user;
                    if (!empty($user)) {
                        $user['id'] = (string) $user['id'];
                    }
                    return $user;
                },
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt);
                }
            ]
        );
    }

    /**
     * Get score history by params
     * @param array $params
     * @param MongoId $accountId
     * @author Rex Chen
     */
    public static function search($params, $accountId)
    {
        $condition = ['accountId' => $accountId];
        if (!empty($params['memberId'])) {
            $condition['memberId'] = $params['memberId'];
        }
        $query = self::find();
        $query->where($condition);
        $query->orderBy(self::normalizeOrderBy($params));

        return new ActiveDataProvider(['query' => $query]);
    }

    public static function getMemberCountToDay($accountId)
    {
        $today = new MongoDate(strtotime(date('Y-m-d')));
        // return count(self::getCollection()->distinct("memberId"));
        return count(self::distinct("memberId", ['createdAt' => ['$gt' => $today], 'accountId' => $accountId]));
    }

    public static function getTotalScoreToday($accountId)
    {
        $today = new MongoDate(TimeUtil::today());

        $raw = self::getCollection()->aggregate(
            [
                ['$match' => ['createdAt' => ['$gt' => $today], 'accountId' => $accountId]],
                ['$group' => ['_id' => null, 'totalScore' => ['$sum' => '$increment']]]
            ]
        );

        if (empty($raw)) {
            return 0;
        }

        return $raw[0]['totalScore'];
    }

    public static function getTotalScoreYesterday($accountId)
    {
        $todayTimeStamp = TimeUtil::today();
        $today = new MongoDate($todayTimeStamp);
        $yesterday = new MongoDate($todayTimeStamp - 24 * 3600);

        $raw = self::getCollection()->aggregate(
            [
                ['$match' => ['createdAt' => ['$lte' => $today, '$gt' => $yesterday], 'accountId' => $accountId]],
                ['$group' => ['_id' => null, 'totalScore' => ['$sum' => '$increment']]]
            ]
        );

        if (empty($raw)) {
            return 0;
        }

        return $raw[0]['totalScore'];
    }

    public static function countByRuleName($ruleName, $accountId)
    {
        return self::count(['description' => $ruleName, 'brief' => self::ASSIGNER_RULE, 'accountId' => $accountId]);
    }

    public static function distinctMemberIdByRuleName($ruleName, $accountId)
    {
        return self::distinct("memberId", ['description' => $ruleName, 'brief' => self::ASSIGNER_RULE, 'accountId' => $accountId]);
    }

    /**
     * Get by score rule name
     * @param string $description
     * @param string $memberId
     * @param string $accountId
     * @param string $assigner
     * @param string $timeFrom
     */
    public static function getByRuleName($ruleName, $memberId, $accountId, $timeFrom = 0)
    {
        return self::findOne([
            'brief' => self::ASSIGNER_RULE,
            'description' => $ruleName,
            'memberId' => $memberId,
            'accountId' => $accountId,
            'createdAt' => ['$gte' => new MongoDate($timeFrom)]
        ]);
    }

    public static function getAllMemberIdByRuleName($ruleName, $memberIds, $accountId, $timeFrom = 0)
    {
        if (is_string($memberIds)) {
            $memberIds = [$memberIds];
        }
        $where = [
            'brief' => self::ASSIGNER_RULE,
            'description' => $ruleName,
            'memberId' => ['$in' => $memberIds],
            'accountId' => $accountId,
            'createdAt' => ['$gte' => new MongoDate($timeFrom)]
        ];
        return self::distinct('memberId', $where);
    }

    public static function getLastByMemberId($memberId)
    {
        $condition = ['memberId' => $memberId];
        return self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
    }

    /**
     * record the score of member where the score come from
     */
    public static function recordScore($data)
    {
        $scoreHistory = new ScoreHistory();
        $scoreHistory->load($data, '');
        if (false === $scoreHistory->save()) {
            return  false;
        } else {
            return $scoreHistory;
        }
    }

    /**
     * Update member card after save member.
     * @see \yii\db\BaseActiveRecord::afterSave($insert, $changedAttributes)
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $member = Member::findByPk($this->memberId);
            if (!empty($member)) {
                $member->upgradeCard();
            }
        }
    }

    /**
     * Record history by score rule
     * @param ScoreRule $scoreRule
     * @param MongoId $memberId
     * @param array $channel
     * @return boolean
     */
    public static function recordByScoreRule($scoreRule, $memberId, $channel)
    {
        $scoreHistory = new ScoreHistory();
        $scoreHistory->assigner = self::ASSIGNER_RULE;
        $scoreHistory->increment = $scoreRule->score + 0;
        $scoreHistory->memberId = $memberId;
        $scoreHistory->brief = self::ASSIGNER_RULE;
        $scoreHistory->description = $scoreRule->name;
        $scoreHistory->channel = $channel;
        $scoreHistory->accountId = $scoreRule->accountId;
        return $scoreHistory->save();
    }
}
