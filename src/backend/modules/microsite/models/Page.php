<?php
namespace backend\modules\microsite\models;

use backend\components\BaseModel;
use backend\utils\StringUtil;
use yii\helpers\Json;
use backend\utils\MongodbUtil;

/**
 * Model class for page
 *
 * @property MongoId    $_id
 * @property string     $title
 * @property string     $description
 * @property boolean    $isFinished
 * @property string     $type              'cover' or 'normal'
 * @property string     $color
 * @property array      $creator           ['id' => MongoId, 'name' => string]
 * @property array      $cpts              component config list
 * @property string     $url
 * @property string     $shortUrl
 * @property int        $count
 * @property boolean    $deletable
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property string     accountId
 */
class Page extends BaseModel
{
    /**
     * The type of the normal page.
     */
    const TYPE_NORMAL = 'normal';
    /**
     * The type of the cover page.
     */
    const TYPE_COVER  = 'cover';

    /**
     * The defalut color for page
     */
    const DEFAULT_COLOR = '#6ab3f7';

    /**
     * Declares the name of the Mongo collection associated with ChatConversation.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'page';
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
            ['title', 'description', 'url', 'shortUrl', 'isFinished', 'type', 'color', 'creator', 'cpts', 'count', 'deletable']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['title', 'description', 'type', 'color', 'cpts', 'isFinished', 'deletable']
        );
    }
    /**
     * Returns a list of scenarios and the corresponding active attributes.
     * Add the 'createBasic' and 'addComponents' scenario and update 'update' scenario
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['createBasic'] = ['title', 'url', 'shortUrl', 'description', 'type', 'isFinished', 'count', 'deletable', 'color'];
        $scenarios['addComponents'] = ['color', 'cpts'];
        $scenarios[self::SCENARIO_UPDATE] = ['title', 'description'];
        return $scenarios;
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
                // the title and description attributes are required
                [['title', 'url', 'shortUrl', 'description', 'accountId'], 'required'],
                ['color', 'default', 'value' => self::DEFAULT_COLOR],
                ['type', 'default', 'value' => self::TYPE_NORMAL],
                ['isFinished', 'default', 'value' => false],
                ['count', 'default', 'value' => 0],
                ['deletable', 'default', 'value' => true]
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
            [
                'title', 'description', 'isFinished', 'type', 'color', 'cpts', 'url', 'shortUrl', 'count', 'deletable',
                'creator' => function () {
                    $creator = $this->creator;
                    $creator['id'] = $creator['id'] . '';
                    return $creator;
                },
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                }
            ]
        );
    }

    /**
     * Get page record by UUID
     * @param  MongoId $id attribute "_id" for collection "page"
     * @param  Boolean $isFinished attribute "isFinished" for collection "page"
     * @return integer the number of documents updated, should be 1 for success or 0 for fail.
     * @author Vincent Hou
     */
    public static function getPage($id, $isPreview)
    {
        return $isPreview ? parent::findByPk($id) : parent::findByPk($id, ['isFinished' => true]);
    }

    public static function searchByTitleAndUrl($accountId, $limit, $search = null, $createdAt = null)
    {
        $condition = ['isDeleted' => false, 'accountId' => $accountId, 'isFinished' => true];
        if ($search !== null) {
            $search = StringUtil::regStrFormat(trim($search));
            $searchReg = new \MongoRegex("/$search/i");
            $condition['$or'] = [
                ['shortUrl' => $searchReg],
                ['title' => $searchReg]
            ];
        }
        if ($createdAt !== null) {
            $condition['createdAt'] = ['$lt' => $createdAt];
        }

        return self::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->limit($limit)->all();
    }

    public static function getByShortUrl($url)
    {
        return self::findOne(['shortUrl' => $url]);
    }

    public static function getDefaultCover($accountId)
    {
        return self::findOne(['accountId' => $accountId, 'title' => '默认首页', 'deletable' => false]);
    }
}
