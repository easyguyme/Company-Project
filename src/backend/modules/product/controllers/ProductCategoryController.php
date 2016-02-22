<?php
namespace backend\modules\product\controllers;

use backend\modules\product\models\ProductCategory;
use backend\modules\product\models\CategoryProperty;
use backend\modules\product\models\Product;
use backend\models\Token;
use backend\exceptions\InvalidParameterException;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use Yii;
use MongoId;

class ProductCategoryController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\ProductCategory';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['delete'], $actions['update'], $actions['create']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        return ProductCategory::search($params, $accountId);
    }

    public function actionDelete($id)
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        $productCategoryInfo = ProductCategory::findByPk($id);

        $categoryIds = explode(',', $id);
        //check the category to make sure the category have not product
        foreach ($categoryIds as $key => $categoryId) {
            $categoryIds[$key] = new MongoId($categoryId);
        }
        //delete category
        ProductCategory::deleteAll(['_id' => ['$in' => $categoryIds]]);
        //delete product category
        Product::updateAll(['category' =>[]], ['category.id' => ['$in' => $categoryIds]]);

        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['name'])) {
            throw new BadRequestHttpException("category required");
        }

        $productCategoryInfo = ProductCategory::findByPk($id);

        $result = ProductCategory::checkGroupNameUnique($params['name'], $productCategoryInfo->type, $accountId);
        if (false == $result) {
            throw new InvalidParameterException([$id => Yii::t("product", "categoryName_isUsed")]);
        }

        $productCategoryInfo->name = $params['name'];
        $productCategoryInfo->save();
        Product::updateAll(['category.name' => $params['name']], ['category.id' => new MongoId($id)]);
        return $productCategoryInfo;
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (empty($params['name']) || empty($params['type'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = ProductCategory::checkGroupNameUnique($params['name'], $params['type'], $accountId);
        if (false === $result) {
            throw new InvalidParameterException(['categoryName' => Yii::t("product", "categoryName_isUsed")]);
        }

        $productcategory = new ProductCategory();
        $params['accountId'] = $accountId;
        $productcategory->load($params, '');

        if ($productcategory->save()) {
            return $productcategory;
        }
        throw new ServerErrorHttpException('Failed to create the category.');
    }
}
