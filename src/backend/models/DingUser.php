<?php
namespace backend\models;

use Yii;
use backend\components\PlainModel;
use backend\components\ActiveDataProvider;

/**
 * Model class for staff.
 * The followings are the available columns in collection 'dingUser':
 * @property MongoId $_id
 * @property MongoId $corpId
 * @property string $dingUserId
 * @property string $name
 * @property string $avatar
 * @property string $mobile
 * @property string $email
 * @property string $openId
 * @property array $enableActions
 * @property MongoDate $updatedAt
 * @property MongoId $accountId
 **/

class DingUser extends PlainModel
{
    const ACTION_MOBILE_POS = 'mobile_pos';
    const ACTION_HELPDESK = 'helpdesk';

    /**
    * Declares the name of the Mongo collection associated with dingUser.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'dingUser';
    }

    /**
    * Returns the list of all attribute names of dingUser.
    * This method must be overridden by child classes to define available attributes.
    * @return array list of attribute names.
    */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['corpId', 'dingUserId', 'name', 'avatar', 'mobile','email', 'openId', 'enableActions']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['corpId', 'dingUserId', 'name', 'avatar', 'mobile','email', 'openId', 'enableActions']
        );
    }
    /**
    * Returns the list of all rules of dingUser.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['enableActions', 'default', 'value' => []]
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'corpId', 'dingUserId', 'name', 'avatar', 'mobile','email', 'openId', 'enableActions'
            ]
        );
    }

    public static function getByCorpIdAndDingUserId($accountId, $corpId, $dingUserId)
    {
        return self::findOne(['accountId' => $accountId, 'corpId' => $corpId, 'dingUserId' => $dingUserId]);
    }

    /**
     * Authorize the users with specified authorities
     * @param  array   $users       user name list
     * @param  array   $authorities authority list
     * @param  MongoId $accountId
     * @return integer the number of documents updated.
     */
    public static function authorize($users, $authorities, $accountId)
    {
        $condition = ['_id' => ['$in' => $users], 'accountId' => $accountId];
        return self::updateAll(['enableActions' => $authorities], $condition);
    }
}
