<?php
namespace backend\modules\product\controllers;

use backend\modules\product\models\Product;
use backend\modules\product\models\ProductCategory;
use backend\modules\product\models\ProductInfo;
use backend\modules\product\models\PromotionCode;
use backend\modules\product\models\Campaign;
use backend\modules\product\models\PromotionCodeAnalysis;
use backend\models\Token;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\models\Goods;
use Yii;
use backend\behaviors\ProductBehavior;
use backend\models\StoreGoods;
use backend\utils\LogUtil;
use backend\modules\reservation\models\ReservationGoods;
use MongoId;
use backend\utils\UrlUtil;

class ProductController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\Product';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        $provider = Product::search($params, $accountId);
        if (!empty($params['storeId'])) {
            $products = [];
            foreach ($provider->getModels() as $product) {
                $storeGoods = StoreGoods::getByProductAndStore($product->_id, new MongoId($params['storeId']));
                $product = $this->serializeData($product);
                $product['isStoreGoods'] = !empty($storeGoods);
                $products[] = $product;
            }
            $provider->setModels($products);
        }

        if (!empty($params['shelfId'])) {
            $products = [];
            foreach ($provider->getModels() as $product) {
                $reservationGoods = ReservationGoods::findOne(['reservationShelf.id' => new MongoId($params['shelfId']), 'productId' => $product->_id]);
                $product = $this->serializeData($product);
                $product['isReservationGoods'] = !empty($reservationGoods);
                $products[] = $product;
            }
            $provider->setModels($products);
        }

        return $provider;
    }

    public function actionCreate()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;

        $productInfo = isset($params['intro']) ? $params['intro'] : '';
        unset($params['intro']);
        //check the property of the category
        if (empty($params['sku'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        Product::checkSkuWithCreateProduct($params['sku'], $accountId);
        Product::checkCategory($params, $accountId);

        //conver specifications
        if (isset($params['specifications'])) {
            $params['specifications'] = Product::packSpecifications($params['specifications']);
        }

        $product = new Product();
        $product->load($params, '');

        if ($product->save()) {
            //add description
            $product->addIntro($product, $productInfo);
            $product->intro = $productInfo;
            $product->_id .= '';

            $args = [
                'url' => UrlUtil::getDomain() . '/mobile/product/info?productId=' . $product->_id,
                'model' => 'backend\modules\product\models\Product',
                'qrcodeType' => 'product',
                'associatedId' => (string)$product->_id,
                'accountId' => (string)$product->accountId,
            ];
            $jobId = Yii::$app->job->create('backend\modules\common\job\CreateQrcode', $args);
            LogUtil::info(['message' => 'begin to create a job to create product qrcode', 'jobId' => $jobId], 'resque');

            return $product;
        } else {
            throw new ServerErrorHttpException('Fail to create member');
        }
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        //check the product name
        $id = new MongoId($id);
        $where = ["_id" => $id];
        $product = Product::findOne($where);
        if (empty($product)) {
            throw new InvalidParameterException(Yii::t('product', 'product_deleted'));
        }
        if (!empty($params['sku'])) {
            //check the sku whether exists
            Product::checkSKuWithUpdateProduct($params['sku'], $id, $accountId);
        }
        //check the property whether to be required
        Product::checkCategory($params, $accountId);

        $intro = isset($params['intro']) ? $params['intro'] : " ";
        unset($params['intro']);

        //conver specifications
        if (isset($params['specifications'])) {
            $params['specifications'] = Product::packSpecifications($params['specifications']);
        }

        $product->load($params, '');

        if ($product->save()) {
            //update goods and storeGoods
            $this->attachBehavior('ProductBehavior', new ProductBehavior);
            $this->update($product);
            //update product specification price
            if (isset($params['specifications'])) {
                $this->updateSpecificationPrice($product, $params['specifications']);
            }
            //update the intro
            $this->updateProductInfo($product, $intro);

            if (empty($product->qrcode)) {
                $args = [
                    'url' => UrlUtil::getDomain() . '/mobile/product/info?productId=' . $product->_id,
                    'model' => 'backend\modules\product\models\Product',
                    'qrcodeType' => 'product',
                    'associatedId' => (string)$product->_id,
                    'accountId' => (string)$product->accountId,
                ];
                $jobId = Yii::$app->job->create('backend\modules\common\job\CreateQrcode', $args);
            }
            return $product;
        } else {
            throw new ServerErrorHttpException('Fail to update product');
        }
    }

    private function updateProductInfo($product, $info)
    {
        $productInfo = ProductInfo::findByPk($product->_id);
        if (empty($productInfo)) {
            $product->addIntro($product, $info);
        } else {
            $productInfo->accountId = $product->accountId;
            $productInfo->intro = $info;
            $productInfo->save();
        }
    }

    public function actionDelete($id)
    {
        $idstrList = explode(',', $id);
        $ids = [];
        foreach ($idstrList as $perId) {
            $ids[] = new MongoId($perId);
        }
        $where = ['_id' => ['$in' => $ids]];
        $goodsWhere = ['productId' => ['$in' => $ids]];

        $result = Product::findOne(array_merge($where, ['isBindCode' => true]));

        if (!empty($result)) {
            PromotionCode::deleteAll(['productId' => ['$in' => $ids]]);
        }
        if (!empty(Campaign::getByProductIds($ids))
            || !empty(Goods::findOne($goodsWhere))
            || !empty(Yii::$app->service->setAccountId($this->getAccountId())->reservationOrder->getByProductIds($ids))) {
            throw new BadRequestHttpException(Yii::t('product', 'can_not_delete'));
        }
        if (Product::deleteAll($where) == false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
        //delete the product intro
        ProductInfo::deleteAll($where);
        //delete goods and storeGoods
        $this->attachBehavior('ProductBehavior', new ProductBehavior);
        $this->delete($ids);

        Yii::$app->getResponse()->setStatusCode(204);
    }

    /**
    * get the name from product by string
    * */
    public function actionName()
    {
        $params = $this->getQuery();

        if (empty($params['id'])) {
            throw new BadRequestHttpException('missing params');
        }
        $accountId = $this->getAccountId();

        $condition['id'] = [];
        $ids = explode(',', $params['id']);
        foreach ($ids as $key => $id) {
            $condition['id'][] = new MongoId($id);
        }

        $products = Product::getProductsName($condition, $accountId);

        return array_map(function ($product) {
            return $product->name;
        }, $products);
    }

    public function actionGetProductSku()
    {
        return Product::createSku();
    }
}
