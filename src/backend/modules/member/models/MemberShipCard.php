<?php
namespace backend\modules\member\models;

use backend\components\BaseModel;
use Yii;
use backend\utils\MongodbUtil;

/**
 * Model class for MemberShipCard.
 *
 * The followings are the available columns in collection 'memberShipCard':
 * @property MongoId   $_id
 * @property string    $name
 * @property string    $poster
 * @property string    $fontColor
 * @property string    $privilege
 * @property Array     $condition: {$minScore, $maxScore}
 * @property string    $usageGuide
 * @property boolean   $isEnabled
 * @property boolean   $isDefault
 * @property Array     $scoreResetDate: {$month, $day}
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property int       $scoreResetDate
 **/
class MemberShipCard extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with MemberShipCard.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'memberShipCard';
    }

    /**
     * Returns the list of all attribute names of MemberShipCard.
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
            ['name', 'poster', 'fontColor', 'privilege', 'condition', 'usageGuide', 'isEnabled', 'isDefault', 'isAutoUpgrade', 'scoreResetDate']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'poster', 'fontColor', 'privilege', 'condition', 'usageGuide', 'isEnabled', 'isDefault', 'isAutoUpgrade', 'scoreResetDate']
        );
    }

    /**
     * Returns the list of all rules of MemberShipCard.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name', 'poster', 'privilege', 'usageGuide'], 'required'],
                ['isEnabled', 'default', 'value' => true],
                ['isDefault', 'default', 'value' => false],
                ['isAutoUpgrade', 'default', 'value' => true]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into MemberShipCard.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'poster', 'fontColor', 'privilege', 'condition', 'usageGuide', 'isEnabled', 'isDefault', 'isAutoUpgrade', 'scoreResetDate',
                'provideCount' => function () {
                    return Member::count(['cardId' => $this->_id]);
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'updatedAt' => function () {
                    return MongodbUtil::MongoDate2String($this->updatedAt, 'Y-m-d H:i:s');
                }
            ]
        );
    }

    /**
     * Get default membership card
     * @param  MongoId $accountId
     * @return object<MemberShipCard> or null
     */
    public static function getDefault($accountId)
    {
        return self::findOne(['accountId' => $accountId, 'isDefault' => true]);
    }

    /**
     * Get MemberShipCard by score
     * @param int $score
     * @return Array member info
     */
    public static function getByScore($score, $accountId)
    {
        $condition = [
            'isAutoUpgrade' => true,
            'condition.minScore' => ['$lte' => $score],
            'condition.maxScore' => ['$gte' => $score],
            'accountId' => $accountId,
        ];
        return self::findOne($condition);
    }

    /**
     * Get AutoUpgrade MemberShipCard by score oreder by condition.maxScore asc
     * @param mongoId $accountId
     */
    public static function getAutoUpgradeByAccount($accountId)
    {
        $condition = [
            'isAutoUpgrade' => true,
            'accountId' => $accountId,
            'isDeleted' => self::NOT_DELETED
        ];
        return self::find()->where($condition)->orderBy(['condition.maxScore' => SORT_ASC])->all();
    }

    /**
     * Get MemberShipCard by score oreder by condition.maxScore asc
     * @param mongoId $accountId
     */
    public static function getByAccount($accountId)
    {
        $condition = [
            'accountId' => $accountId,
            'isDeleted' => self::NOT_DELETED
        ];
        return self::find()->where($condition)->orderBy(['condition.maxScore' => SORT_ASC])->all();
    }

    /**
     * Get by name and accountId
     * @param string $name
     */
    public static function getByName($name, $accountId)
    {
        return self::findOne(['name' => $name, 'accountId' => $accountId]);
    }

    /**
     * Get max level card
     * @param mongoId $accountId
     */
    public static function getMaxCard($accountId)
    {
        $condition = [
            'isAutoUpgrade' => true,
            'accountId' => $accountId,
            'isDeleted' => parent::NOT_DELETED,
        ];
        return self::find()->where($condition)->orderBy(['condition.maxScore' => SORT_DESC])->one();
    }

    public static function getMaxCardByScore($score, $accountId)
    {
        $condition = [
            'isAutoUpgrade' => true,
            'accountId' => $accountId,
            'isDeleted' => parent::NOT_DELETED,
            'condition.minScore' => ['$lte' => $score]
        ];
        return self::find()->where($condition)->orderBy(['condition.maxScore' => SORT_DESC])->one();
    }

    public static function getSuitableCard($score, $accountId)
    {
        $card = self::getMaxCardByScore($score, $accountId);
        if (empty($card)) {
            $card = self::getDefault($accountId);
        }
        return $card;
    }
}
