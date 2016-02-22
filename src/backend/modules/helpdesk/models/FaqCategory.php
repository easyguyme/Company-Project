<?php
namespace backend\modules\helpdesk\models;

use backend\components\BaseModel;
use Yii;
use backend\modules\helpdesk\models\Faq;

/**
 * Model class for FAQCategory.
 *
 * The followings are the available columns in collection 'FaqCategory':
 * @property MongoId   $_id
 * @property string    $name
 * @property ObjectId  $accountId
 * @property boolean   $isDeleted
 * @property boolean   $isDefault
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 */
class FaqCategory extends BaseModel
{
    // instance for FaqCategory
    private static $_instance;

    // marke whether need to get questions
    public static $isFetchFaqs = false;

    /**
     * Declares the name of the Mongo collection associated with FaqCategory.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'faqCategory';
    }

    /**
     * Returns the list of all attribute names of FaqCategory.
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
            ['name', 'isDefault']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'isDefault']
        );
    }

    /**
     * Returns the list of all rules of FaqCategory.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['name', 'required'],
                ['isDefault', 'default', 'value' => false],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into FaqCategory.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name', 'questions' =>function($model) {
                if (self::$isFetchFaqs) {
                    return $this->getQuestions();
                }
                return '';
            }, 'isDefault']
        );
    }

    public static function getDefault(\MongoId $accountId)
    {
        return self::findOne(['isDefault' => true, 'accountId' => $accountId]);
    }

    public static function getAll($accountId)
    {
        $condition = ['isDeleted' => false, 'accountId' => $accountId];
        $results = self::find()->where($condition)->orderBy(['createdAt' => SORT_ASC])->all();
        $categories = self::sortCategories($results);
        return $categories;
    }

    public static function search($condition = [])
    {
        return self::find()->where($condition)->all();
    }

    public static function getByName($name, $accountId)
    {
        $condition = ['accountId' => $accountId, 'name' => $name];
        return self::findByCondition($condition, true);
    }

    public function getQuestions()
    {
        $condition = ['isDeleted' => false, 'accountId' => $this->accountId, 'faqCategoryId' => $this->_id . ''];
        return Faq::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->all();
    }

    public static function sortCategories($categories)
    {
        if (!empty($categories)) {
            $index = 0;
            foreach ($categories as $category) {
                if ($category['isDefault']) {
                    unset($categories[$index]);
                    array_unshift($categories, $category);
                    return $categories;
                }
                $index++;
            }
        }
        return $categories;
    }
}
