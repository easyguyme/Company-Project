<?php
namespace backend\modules\helpdesk\models;

use backend\components\BaseModel;
use Yii;

/**
 * Model class for FAQ.
 *
 * The followings are the available columns in collection 'FAQ':
 * @property MongoId   $_id
 * @property string    $question
 * @property string    $answer
 * @property string    $faqCategoryId
 * @property ObjectId  $accountId
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 */
class Faq extends BaseModel
{
    //instance for FAQ
    private static $_instance;

    /**
     * Declares the name of the Mongo collection associated with FAQ.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'faq';
    }

    /**
     * Returns the list of all attribute names of FAQ.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted', 'accountId'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['question', 'answer', 'faqCategoryId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['question', 'answer', 'faqCategoryId']
        );
    }

    /**
     * Returns the list of all rules of FAQ.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['question', 'answer', 'faqCategoryId'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into FAQ.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['question', 'answer', 'faqCategoryId']
        );
    }

    public static function search($condition = [], $offset = 0, $limit = 100)
    {
        $query = self::find();
        $query->orderBy(['createdAt' => SORT_DESC]);
        $query->where($condition)->offset($offset)->limit($limit);
        return $query->all();
    }

    public static function count($condition = [])
    {
        $query = self::find();
        $query->where($condition);
        return $query->count();
    }
}
