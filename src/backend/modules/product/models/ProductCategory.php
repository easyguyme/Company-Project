<?php
namespace backend\modules\product\models;

use backend\components\BaseModel;
use backend\utils\StringUtil;
use yii\web\ServerErrorHttpException;
use MongoId;

/**
 * Model class for ProductCategory
 *
 * @property MongoId    $_id
 * @property MongoId    $productId
 * @property string     $name
 * @property array      $properties:[id,order,name,options,defaultValue,isRequired]
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 */

class ProductCategory extends BaseModel
{
    const RESERVATION_CATEGORY_NAME = 'service';
    const RESERVATION_CATEGORY_PROPERTY_NAME = 'price';

    const PRODUCT = 'product';
    const RESERVATION = 'reservation';

    /**
     * Declares the name of the Mongo collection associated with productCategory.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'productCategory';
    }

    /**
     * Returns the list of all attribute names of productCategory.
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
            ['name', 'properties', 'type']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'properties', 'type']
        );
    }

    /**
     * Returns the list of all rules of productCategory.
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
                ['properties', 'default', 'value' => []],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into productCategory.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name', 'properties', 'type',
                'isDeleteCategory' => function () {
                    $condition = ['category.id' => $this->_id];
                    $info = Product::findOne($condition);
                    if (!empty($info) || self::RESERVATION_CATEGORY_NAME == $this->name) {
                        return false;
                    } else {
                        return true;
                    }
                }
            ]
        );
    }

    /**
     * format the property struct
     * @return array,['properties' => [], 'uuid' => 'xx']
     * @param $param ,array
     */
    public function formatPropertiesStr($params)
    {
        $rule = ['id', 'order', 'name'];
        $uuid = StringUtil::uuid();
        if (is_array($params)) {
            foreach ($params as $key => $param) {
                if (!in_array($key, $rule)) {
                    unset($params[$key]);
                }
            }
            $params['id'] = $uuid;
        }

        if (is_array($this->properties)) {
            if (!empty($this->properties)) {
                foreach ($this->properties as $value) {
                    $data[] = $value;
                }
                array_push($data, $params);
            } else {
                $data[] = $params;
            }
        }
        return ['properties' => $data, 'uuid' => $uuid];
    }

    /**
     * get the category info
     * @param $name,string
     */
    public static function getByName($name)
    {
        return self::findOne(['name' => $name]);
    }

    /**
     * search for the message
     * @param $params,array
     * @param $accountId,mongoId
     */
    public static function search($params, $accountId)
    {
        $condition = ['accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];

        if (!empty($params['type'])) {
            $condition['type'] = $params['type'];
        }
        //$orderBy = ['createdAt' => SORT_DESC];
        $orderBy = self::normalizeOrderBy($params);
        $productCategoryInfos = ProductCategory::find()->where($condition)->orderBy($orderBy)->all();
        if (!empty($productCategoryInfos)) {
            foreach ($productCategoryInfos as $k => $productCategoryInfo) {
                $productCategoryInfos[$k]['properties'] = CategoryProperty::showOrderPeoperty($productCategoryInfo->properties);
            }
        }
        return ['items' => $productCategoryInfos];
    }

    /**
    * get category id ,convert string to a array
    * @param $id string
    * @return get category id
    */
    public static function getCategoryList($id, $delimiter = ',')
    {
        $idList = explode($delimiter, $id);
        foreach ($idList as $k => $perId) {
            $idList[$k] = new \MongoId($perId);
        }
        return $idList;
    }

    public static function checkGroupNameUnique($name, $groupType, $accountId)
    {
        $result = ProductCategory::findOne(['name' => $name, 'type' => $groupType, 'accountId' => $accountId]);
        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }
}
