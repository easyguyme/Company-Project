<?php
namespace backend\modules\microsite\models;

use yii\web\ServerErrorHttpException;
use Yii;
use backend\components\BaseModel;
use backend\components\CptConfValidator;

/**
 * Model class for pageComponent.
 *
 * The followings are the available columns in collection 'pageComponent':
 * @property MongoId    $_id
 * @property MongoId    $parentId
 * @property MongoId    $pageId
 * @property string     $name
 * @property array      $jsonConfig
 * @property string     $color
 * @property int        $order
 * @property int        $tabIndex
 * @property array      $tabs
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property string     $tabs
 * @author Harry Sun
 **/
class PageComponent extends BaseModel
{
    /**
     * Component type tab
     **/
    const COMPONENT_TYPE_TAB = 'tab';

    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'pageComponent';
    }

    /**
     * Returns a list of scenarios and the corresponding active attributes.
     * An active attribute is one that is subject to validation in the current scenario.
     * The returned array should be in the following format:
     *
     * ~~~
     * [
     *     'scenario1' => ['attribute11', 'attribute12', ...],
     *     'scenario2' => ['attribute21', 'attribute22', ...],
     *     ...
     * ]
     * ~~~
     *
     * By default, an active attribute is considered safe and can be massively assigned.
     * If an attribute should NOT be massively assigned (thus considered unsafe),
     * please prefix the attribute with an exclamation character (e.g. '!rank').
     *
     * The default implementation of this method will return all scenarios found in the [[rules()]]
     * declaration. Three special scenarios named [[SCENARIO_DEFAULT]], [[SCENARIO_CREATE]]
     * and [[SCENARIO_UPDATE]] will contain all attributes found in the [[rules()]].
     * Each scenario will be associated with the attributes that
     * are being validated by the validation rules that apply to the scenario.
     *
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['parentId', 'pageId', 'name', 'color', 'order'];
        $scenarios[self::SCENARIO_UPDATE] = ['jsonConfig'];
        return $scenarios;
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
            ['parentId', 'pageId', 'name', 'jsonConfig', 'color', 'order', 'tabIndex', 'tabs']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['parentId', 'pageId', 'name', 'jsonConfig', 'color', 'order', 'tabIndex', 'tabs']
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
                [['parentId', 'pageId', 'name', 'jsonConfig', 'color', 'order'], 'required'],
                ['parentId', 'toMongoId'],
                ['pageId', 'toMongoId'],
                ['jsonConfig', 'validateConfig']
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
                'parentId' => function($model) {
                    return $model->_id . '';
                },
                'pageId' => function($model) {
                    return $model->_id . '';
                },
                'name', 'jsonConfig', 'color', 'order', 'tabIndex', 'tabs'
            ]
        );
    }

    /**
     * Get components by parent id and tab index (optional)
     * @param  MongoId $parentId parent id
     * @param  Integer $tabIndex the index of tab (0 or 1)
     */
    public static function getByParentId($parentId, $tabIndex = null)
    {
        $condition = ['parentId' => $parentId, 'isDeleted' => BaseModel::NOT_DELETED];
        if (!is_null($tabIndex)) {
            $condition['tabIndex'] = $tabIndex;
        }
        return self::find()->where($condition)->orderBy(['order' => SORT_ASC])->all();
    }

    /**
     * validator for jsonConfig field
     */
    public function validateConfig($attribute)
    {
        CptConfValidator::validate($this->name, $this->$attribute);
    }

    /**
     * get all components with page id
     * @param  MongoId $pageId microsite page id
     */
    public static function getAllComponents($pageId)
    {
        $pageComponents = PageComponent::getByParentId($pageId);

        foreach ($pageComponents as $pageComponent) {
            if ($pageComponent->name == PageComponent::COMPONENT_TYPE_TAB) {
                $parentId = $pageComponent->_id;

                $componentTabs = $pageComponent->jsonConfig['tabs'];
                $tabIndex = 0;
                foreach ($componentTabs as &$componentTab) {
                    $tabs = PageComponent::getByParentId($parentId, $tabIndex);
                    $componentTab['cpts'] = $tabs;
                    $tabIndex++;
                }

                $pageComponent->jsonConfig = ['tabs' => $componentTabs];
            }
        }

        return $pageComponents;
    }
}
