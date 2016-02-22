<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\models\Account;
use backend\utils\MongodbUtil;
use backend\utils\StringUtil;

/**
 * This is the user model class for aug-marketing
 *
 * The followings are the available columns in collection 'validation':
 * @property MongoId $_id
 * @property string $code
 * @property MongoId $userId
 * @property MongoDate $expire
 * @property boolean $isDeleted
 * @property boolean $toValidateAccount
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @author Devin Jin
 **/
class Validation extends BaseModel
{
    const LINK_INVALID = '1';
    const LINK_EXPIRED = '2';
    const USER_DELETED = '3';
    const USER_ACTIVATED = '4';
    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'validation';
    }

    /**
     * Returns the list of all attribute names of user.
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
            ['code', 'expire', 'userId', 'toValidateAccount']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes()
        );
    }

    /**
     * Returns the list of all rules of user.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['code', 'default', 'value' => StringUtil::uuid()],
                ['toValidateAccount', 'default', 'value' => false]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields()
        );
    }

    /**
     * Validate code when activate user
     * @param $code, String.
     * @return String, error code or userId
     *
     * @author Sara Zhang
     */
    public static function validateCode($code, $isDeleted = true)
    {
        if (empty($code)) {
            return self::LINK_INVALID;
        }

        $validation = Validation::findOne(['code' => $code]);

        if (empty($validation)) {
            return self::LINK_INVALID;
        }

        if (empty($validation->expire) || MongodbUtil::isExpired($validation->expire)) {
            return self::LINK_EXPIRED;
        }

        $userId = $validation->userId;

        if ($validation->toValidateAccount) {
            $user = User::findOne(['_id' => $userId]);
            $attributes = [
                'status' => Account::STATUS_ACTIVATED,
                'trialStartAt' => new \MongoDate(),
                'trialEndAt' => new \MongoDate(strtotime("+30 day")),
            ];
            Account::updateAll($attributes, ['_id' => $user->accountId]);
        }

        if ($isDeleted) {
            $validation->delete();
        }

        return $userId;
    }

    /**
     * Get by user id
     * @param mongoId $userId
     * @return Validation
     */
    public static function getByUserId($userId)
    {
        return self::findOne(['userId' => $userId]);
    }
}
