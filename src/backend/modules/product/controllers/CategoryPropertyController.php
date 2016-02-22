<?php
namespace backend\modules\product\controllers;

use backend\exceptions\InvalidParameterException;
use backend\modules\product\models\Product;
use backend\modules\product\models\ProductCategory;
use backend\modules\product\models\CategoryProperty;
use backend\modules\member\models\MemberProperty;
use backend\models\Token;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use Yii;
use MongoId;

class CategoryPropertyController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\CategoryProperty';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['view'], $actions['update']);
        return $actions;
    }
    /*
    *order the property
    */
    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        if (!isset($params['categoryId'])) {
            throw new BadRequestHttpException('categoryId params missing');
        }
         return CategoryProperty::orderProperty($params, $accountId);
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (!isset($params['name'])) {
            throw new InvalidParameterException(['categoryPropertyName' => Yii::t("product", "property_required")]);
        }

        $categoryId = new MongoId($params['categoryId']);
        $productcategory = ProductCategory::findByPk($categoryId);
        $propertyName = $params['name'];

        if (empty($productcategory)) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        if (count($productcategory->properties) >= 100) {
            throw new BadRequestHttpException(Yii::t('product', 'property_too_large'));
        }

        $result = CategoryProperty::checkPropertyNameUnique($propertyName, $categoryId, $productcategory->type);
        if (false === $result) {
            throw new InvalidParameterException(['categoryPropertyName' => Yii::t("product", "propertyName_isUsed")]);
        }

        //add product properties
        $params = $productcategory->formatPropertiesStr($params);
        $productcategory->properties = $params['properties'];
        if ($productcategory->save()) {
            //update the product category
            $addproperties = ['id' => $params['uuid'], 'name' => $propertyName, 'value' => ''];
            CategoryProperty::updateProductPropertyByCreateProperty($addproperties, $productcategory->_id);
            foreach ($productcategory->properties as $property) {
                if ($property['id'] == $params['uuid']) {
                    return $property;
                }
            }
        } else {
            throw new ServerErrorHttpException('Fail to create property');
        }
    }

    public function actionDelete($id)
    {
        $params = $this->getParams();
        if (!isset($params['propertyId'])) {
            throw new BadRequestHttpException("propertyId params missing");
        }
        $accountId = $this->getAccountId();

        ProductCategory::updateAll(['$pull' => ['properties' => ['id' => $params['propertyId']]]], ['_id' => new MongoId($id)]);

        CategoryProperty::updateProductProperty($params['propertyId'], new MongoId($id), 'delete');
        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();

        $accountId = $this->getAccountId();

        $categoryId = new MongoId($id);
        $where = ['_id' => $categoryId, 'accountId' => $accountId];
        $productcategory = ProductCategory::findOne($where);

        if (!empty($params['order'])) {
            //update the order
            $productcategory = CategoryProperty::updatePropertyOrder($productcategory, $params);
            //return the category id and  property
            $data['id'] = $id;
            $data['properties'] = CategoryProperty::showOrderPeoperty($productcategory->properties);
            return $data;
        } else {
            //update the property not include order
            if (!isset($params['propertyId'])) {
                throw new BadRequestHttpException("propertyId params missing");
            }
            $result = CategoryProperty::checkPropertyNameUnique($params['name'], $categoryId, $productcategory->type);
            if (false === $result) {
                throw new InvalidParameterException(['categoryPropertyName' => Yii::t("product", "propertyName_isUsed")]);
            }

            $productcategory = CategoryProperty::updateProperty($id, $params, $accountId);

            foreach ($productcategory->properties as $key => $properties) {
                if ($properties['id'] == $params['propertyId']) {
                    return $properties;
                }
            }
        }
    }
}
