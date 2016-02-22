<?php
namespace backend\modules\microsite\models;

use backend\components\BaseModel;

/**
 * Model class for ArticleChannel
 *
 * The followings are the available columns in collection 'ArticleChannel':
 * @property MongoId   $_id
 * @property String    $name
 * @property Array     $fields
 * @property MongoId   $accountId
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property Boolean   $isDefault
 **/
class ArticleChannel extends BaseModel
{
    //constants for field type
    const FIELD_TYPE_INPUT = 'input';
    const FIELD_TYPE_TEXTAREA = 'textarea';
    const FIELD_TYPE_DATE = 'date';
    const FIELD_TYPE_TIME = 'time';
    const FIELD_TYPE_IMAGE = 'image';

    /**
     * Declares the name of the Mongo collection associated with ChatConversation.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'articleChannel';
    }

    /**
     * Returns the list of all attribute names of ChatConversation.
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
            ['name', 'fields', 'isDefault']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'fields']
        );
    }

    /**
     * Returns the list of all rules of ChatConversation.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['fields', 'default', 'value' => []],
                ['fields', 'formatFields'],
                ['name', 'required'],
                ['isDefault', 'default', 'value' => false]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into ChatConversation.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'isDefault', 'fields'
            ]
        );
    }

    /**
     * Get the default channel
     * @param  \MongoId $accountId
     * @return ArticleChannel instance.
     */
    public static function getDefault(\MongoId $accountId)
    {
        return self::findOne(['isDefault' => true, 'accountId' => $accountId]);
    }

    public function formatFields($attribute)
    {
        $fieldsFormated = [];

        foreach ($this->fields as $field) {
            if (empty($field['id'])) {
                $field['id'] = (new \MongoId()) . '';
            }

            $fieldsFormated[] = $field;
        }

        $this->fields = $fieldsFormated;
    }

    public static function getByAccount($accountId)
    {
        return self::findAll(['accountId' => $accountId]);
    }
}
