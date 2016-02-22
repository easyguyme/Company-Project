<?php
namespace backend\modules\member\models;

use backend\components\BaseModel;

/**
 * Model class for memberStatistics.
 *
 * The followings are the available columns in collection 'memberStatistics':
 * @property MongoId   $_id
 * @property array     $locationStatistics
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property string    $accountId
 **/
class MemberStatistics extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with memberStatistics.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'memberStatistics';
    }

    /**
     * Returns the list of all attribute names of memberStatistics.
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
            ['locationStatistics']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['locationStatistics']
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

            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into memberStatistics.
     */
    public function fields()
    {
        $fields = array_merge(
            parent::fields(),
            ['locationStatistics']
        );

        return $fields;
    }

    public static function getByAccount($accountId)
    {
        return self::findOne(['accountId' => $accountId]);
    }
}
