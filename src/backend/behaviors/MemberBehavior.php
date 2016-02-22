<?php

namespace backend\behaviors;

use yii\base\Behavior;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\ScoreRule;
use backend\modules\member\models\MemberShipCard;
use backend\modules\member\models\Member;
use Yii;

/**
 * Behavior class file for MemberBehavior
 * Memberbehavior contains the common functions for member module
 * @author Devin Jin
 */
class MemberBehavior extends Behavior
{
    /**
     * @param $member, object
     * @param $rule, string, score rule name
     */
    public function updateItemByScoreRule($member)
    {
        //birthday score
        Member::birthdayReward($member);
        //perfect info score
        $ruleNames = [ScoreRule::NAME_PERFECT_INFO, ScoreRule::NAME_FIRST_CARD];
        foreach ($ruleNames as $ruleName) {
            Member::rewardByScoreRule($ruleName, $member->_id, $member->accountId);
        }
    }

    /**
     * Create default member properties
     */
    public static function createDefaultMemberProperties($accountId)
    {
        $now = time();

        MemberProperty::getCollection()->batchInsert(
            [
                [
                    "order" => 1,
                    "name" => "name",
                    "type" => MemberProperty::TYPE_INPUT,
                    "defaultValue" => "",
                    "isRequired" => true,
                    "isUnique" => true,
                    "isVisible" => true,
                    "isDefault" => true,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isDeleted" => false
                ],
                [
                    "order" => 2,
                    "name" => "tel",
                    "type" => MemberProperty::TYPE_INPUT,
                    "defaultValue" => "",
                    "isRequired" => true,
                    "isUnique" => true,
                    "isVisible" => true,
                    "isDefault" => true,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isDeleted" => false
                ],
                [
                    "order" => 3,
                    "name" => "gender",
                    "type" => MemberProperty::TYPE_RADIO,
                    "defaultValue" => "male",
                    "options" => [
                        "male", "female"
                    ],
                    "isRequired" => false,
                    "isUnique" => false,
                    "isVisible" => true,
                    "isDefault" => true,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isDeleted" => false
                ],
                [
                    "order" => 4,
                    "name" => "birthday",
                    "type" => MemberProperty::TYPE_DATE,
                    "defaultValue" => "",
                    "isRequired" => true,
                    "isUnique" => false,
                    "isVisible" => true,
                    "isDefault" => true,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isDeleted" => false
                ],
                [
                    "order" => 5,
                    "name" => "email",
                    "type" => MemberProperty::TYPE_EMAIL,
                    "defaultValue" => "",
                    "isRequired" => false,
                    "isUnique" => true,
                    "isVisible" => true,
                    "isDefault" => true,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isDeleted" => false
                ]
            ]
        );
    }

    /**
     * create default score rule for the current account
     */
    public static function createDefaultScoreRule($accountId)
    {
        $now = time();

        ScoreRule::getCollection()->batchInsert(
            [
                [
                    'name' => ScoreRule::NAME_PERFECT_INFO,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isEnabled" => false,
                    'isDefault' => true,
                    "isDeleted" => false
                ],
                [
                    'name' => ScoreRule::NAME_BIRTHDAY,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isEnabled" => false,
                    'isDefault' => true,
                    "isDeleted" => false
                ],
                [
                    'name' => ScoreRule::NAME_FIRST_CARD,
                    "accountId" => $accountId,
                    "createdAt" => new \MongoDate($now),
                    "updatedAt" => new \MongoDate($now),
                    "isEnabled" => false,
                    'isDefault' => true,
                    "isDeleted" => false
                ]
            ]
        );
    }

    public static function createDefaultMemberShipCard($accountId)
    {
        $defaultMemberShipCard = Yii::$app->params['default_member_ship_card'];
        $memberShipCard = new MemberShipCard;
        $memberShipCard->attributes = $defaultMemberShipCard;
        $memberShipCard->accountId = $accountId;

        if (!$memberShipCard->save()) {
            throw new Exception("Fail to save default membership card");
        }
    }

    public static function createDefaultData($accountId)
    {
        self::createDefaultScoreRule($accountId);
        self::createDefaultMemberProperties($accountId);
        self::createDefaultMemberShipCard($accountId);
    }
}
