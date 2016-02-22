<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * Model class for account.
 *
 * The followings are the available columns in collection 'accountSetting':
 * @property MongoId $_id
 * @property MongoDate $createdAt
 * @author Vincent Hou
 **/
class ServiceSetting extends PlainModel
{
    /**
     * Declares the name of the Mongo collection.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'serviceSetting';
    }

    /**
     * Returns the list of all attribute names of accountSetting.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'message', 'email'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'message', 'email'
            ]
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
        return parent::rules();
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'message', 'email'
            ]
        );
    }

    public static function findByAccountId($accountId)
    {
        return self::findByCondition(['accountId' => $accountId], true);
    }
}
