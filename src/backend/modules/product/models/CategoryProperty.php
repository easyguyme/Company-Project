<?php
namespace backend\modules\product\models;

use MongoId;
use Yii;
use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use yii\web\BadRequestHttpException;

/**
 * Model class for product.
 *
 **/
class CategoryProperty extends BaseModel
{
    /**
     * Declares the name of the Mongo collection associated with Product.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'productCategory';
    }

    public function scenarios()
    {
        return array_merge(
            parent::scenarios(),
            ['update' => ['name', 'order', 'isRequired']]
        );
    }

    /**
    * Returns the list of all attribute names of Product.
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
            ['order', 'name', 'type', 'options', 'defaultValue', 'isRequired']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['order', 'name', 'type', 'options', 'defaultValue', 'isRequired']
        );
    }

    /**
    * Returns the list of all rules of Product.
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
            ]
        );
    }
    /**
    * The default implementation returns the names of the columns whose values have been populated into Product.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'order', 'name', 'type', 'options', 'defaultValue', 'isRequired',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
                'updatedAt' => function () {
                    return MongodbUtil::MongoDate2String($this->updatedAt, 'Y-m-d H:i:s');
                },
            ]
        );
    }

    public static function updateProductPropertyByCreateProperty($properties, $categoryId)
    {
        $productInfo = Product::findOne(['category.id' => $categoryId]);
        if (!empty($productInfo->category)) {
            if (!empty($productInfo->category['properties'])) {
                $category = [
                    'id' => $categoryId,
                    'name' => $productInfo->category['name'],
                    'properties' => [$properties],
                ];
                Product::updateAll(['category' => $category], ['category.id' =>$categoryId]);
            } else {
                Product::updateAll(['$push' => ['category.properties' => $properties]], ['category.id' => $categoryId]);
            }
        }
    }

    /**
     * update the property in product
     * @param $addproperties  array   the content for the property
     * @param $categoryId  object ID   the category id
     * @param $operation string   the description for operate the product peoperty
     */
    public static function updateProductProperty($addproperties, $categoryId, $operation)
    {
        $productInfo = Product::findOne(['category.id' => $categoryId]);
        if (!empty($productInfo->category)) {
            switch (strtolower($operation)) {
                case 'create':
                    if (empty($productInfo->category['properties'])) {
                        $category = [];
                        $category['id'] = $categoryId;
                        $category['name'] = $productInfo->category['name'];
                        $category['properties'][] = $addproperties;
                        Product::updateAll(['category' => $category], ['category.id' =>$categoryId]);
                    } else {
                        Product::updateAll(['$push' => ['category.properties' => $addproperties]], ['category.id' => $categoryId]);
                    }
                    break;
                case 'delete':
                    if ($productInfo->category['properties']) {
                        Product::updateAll(['$pull' => ['category.properties'=> ['id' => $addproperties]]], ['category.id' => $categoryId]);
                    }
                    break;

                case 'update':
                    Product::updateAll(['$pull' => ['category.properties' =>['id' => $addproperties['id']]]], ['category.id' => $categoryId]);

                    Product::updateAll(['$push' => ['category.properties' => $addproperties]], ['category.id' => $categoryId]);
                    break;
            }
        }
    }

    /**
    * update the order of the property
    * @param $params,array
    * @param $accountId,mongoId
    */
    public static function orderProperty($params, $accountId)
    {
        $where = ['_id' => new MongoId($params['categoryId']), 'accountId' => $accountId];
        $productcategory = ProductCategory::findOne($where);

        if (empty($productcategory)) {
            throw new InvalidParameterException(["product" => \Yii::t('product', 'categoryId_invaild')]);
        }

        $productcategory = self::updatePropertyOrder($productcategory, $params);
        //sort
        return self::showOrderPeoperty($productcategory->properties);
    }

   /**
   * update the property order
   * @param $productcategory,object,productCategory object
   * @param $params,array
   */
    public static function updatePropertyOrder($productcategory, $params)
    {
        if (isset($params['order'])) {
            $properties = $productcategory->properties;
            $orders = $params['order'];
            foreach ($properties as $key => $property) {
                foreach ($orders as $k => $order) {
                    if ($property['id'] == $k) {
                        $properties[$key]['order'] = $order;
                    }
                }
            }
            $productcategory->properties = $properties;
            $productcategory->save();
        }
        return $productcategory;
    }

   /**
   * order the order of the property
   * @param $propertied,array,property of category
   */
    public static function showOrderPeoperty($properties)
    {
        $datas = [];
        foreach ($properties as $value) {
            if (!is_array($value)) {
                return $properties;
            }
            $value['order'] = isset($value['order']) ? $value['order'] : 1;
            $data[$value['order']][] = $value;
        }
        if (!empty($data)) {
            ksort($data);
            foreach ($data as $value) {
                $datas = array_merge($datas, $value);
            }
        }
        return $datas;
    }

    /**
     * update the property
     * @param $id,int,proprty id
     * @param $params,array
     * @param $accountId,MongoId
     */
    public static function updateProperty($id, $params, $accountId)
    {
         //update category property
        $where = ['accountId' => $accountId, '_id' => new \MongoId($id), 'properties.id' => $params['propertyId']];
        $categoryPropertyInfo = ProductCategory::findOne($where);

        if ($categoryPropertyInfo) {
            //only update the name and isrequired
            $addproperties = [];
            if (!empty($categoryPropertyInfo->properties)) {
                $where = ['properties.id' => $params['propertyId']];
                ProductCategory::updateAll(['$set' => ['properties.$.name' => $params['name']]], $where);
            }
            //check whether have properties
            $productInfo = Product::findOne(['category.id' => new \MongoId($id)]);
            if ($productInfo) {
                $where = ['category.properties.id' => $params['propertyId']];
                $updateData = ['category.properties.$.name' => $params['name'], 'category.properties.$.value' => $params['defaultValue']];
                Product::updateAll(['$set' => $updateData], $where);
                unset($productInfo);
            }
            unset($where);
            return $categoryPropertyInfo;
        } else {
            throw new BadRequestHttpException("categoryId or propertyId invalid");
        }
    }


    public static function checkPropertyNameUnique($name, $categoryId, $categoryType)
    {
        $result = ProductCategory::findOne(['properties.name' => $name, '_id' => $categoryId, 'type' => $categoryType]);
        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }
}
