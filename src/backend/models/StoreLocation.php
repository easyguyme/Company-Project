<?php
namespace backend\models;

use backend\components\BaseModel;

/**
 * This is the admin model class for aug-marketing
 *
 * The followings are the available columns in collection 'StoreLocation':
 * @property MongoId    $_id
 * @property string     $name
 * @property string     $parentName
 * @property string     $level
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property MongoId    $accountId
 * @author Harry Sun
 **/
class StoreLocation extends BaseModel
{
    const LOCATION_LEVEL_PROVINCE = 1;
    const LOCATION_LEVEL_CITY = 2;
    const LOCATION_LEVEL_DISTRICT = 3;
    const LOCATION_LEVEL_STORE = 4;

    /**
     * Declares the name of the Mongo collection associated with storeLocation.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'storeLocation';
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
            ['name', 'parentName', 'level']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'parentName', 'level']
        );
    }

    /**
     * Returns the list of all rules of admin.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                // the name, email, password and salt attributes are required
                [['name', 'level'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name']
        );
    }
}
