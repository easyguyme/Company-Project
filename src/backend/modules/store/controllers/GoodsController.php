<?php

namespace backend\modules\store\controllers;

use Yii;
use backend\components\rest\RestController;
use backend\models\StoreGoods;
use backend\modules\product\models\Product;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\models\Store;
use backend\utils\TimeUtil;
use yii\web\ServerErrorHttpException;

class GoodsController extends RestController
{
    public $modelClass = 'backend\models\StoreGoods';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        if (empty($params['storeId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $accountId = $this->getAccountId();

        $params['storeId'] = new \MongoId($params['storeId']);
        return StoreGoods::search($params, $accountId);
    }

    public function actionCreate()
    {
        $goods = $this->getParams('goods');
        $storeId = $this->getParams('storeId');

        $accountId = $this->getAccountId();
        if (empty($goods) || empty($storeId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $storeId = new \MongoId($storeId);
        $store = Store::findByPk($storeId);
        if (empty($store)) {
            throw new InvalidParameterException(Yii::t('common', 'data_error'));
        }
        $productIds = [];
        foreach ($goods as $item) {
            if (!StoreGoods::validatePrice($item['price'])) {
                throw new InvalidParameterException(Yii::t('store', 'price_error'));
            }
            $productIds[] = new \MongoId($item['productId']);
        }
        $storeGoodsCount = StoreGoods::countByProductId($productIds, $storeId);
        if ($storeGoodsCount > 0) {
            throw new InvalidParameterException(Yii::t('store', 'goods_exists'));
        }

        $storeGoods = [];
        foreach ($goods as $item) {
            $product = Product::findByPk($item['productId']);
            if (empty($product)) {
                throw new InvalidParameterException(Yii::t('common', 'data_error'));
            }
            $category = $product->category;
            $pictures = ArrayHelper::getColumn($product->pictures, 'url', false);
            $pictures = array_slice($pictures, 0, 5);
            $storeGoods[] = [
                'storeId' => $storeId,
                'categoryId' => $category['id'],
                'productName' => $product->name,
                'sku' => $product->sku,
                'productId' => $product->_id,
                'pictures' => $pictures,
                'status' => StoreGoods::STATUS_OFF,
                'offShelfTime' => new \Mongodate(),
                'price' => !empty($item['price']) ? floatval($item['price']) : 0.00,
                'accountId' => $accountId
            ];
        }

        if (StoreGoods::batchInsert($storeGoods)) {
            return ['message' => 'OK', 'data' => null];
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    public function actionSale()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        if (empty($params['status']) || empty($params['storeGoodsIds'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $storeGoodsIds = [];
        foreach ($params['storeGoodsIds'] as $id) {
            $storeGoodsIds[] = new \MongoId($id);
        }
        $storeGoods = StoreGoods::getByIds($storeGoodsIds);
        if (count($storeGoods) != count($params['storeGoodsIds'])) {
            throw new InvalidParameterException(Yii::t('store', 'invalid_goods_id'));
        }
        if ($params['status'] == StoreGoods::STATUS_ON) {
            //check the store goods are not on shelves
            if (!empty(StoreGoods::getOnSaleByIds($storeGoodsIds, $accountId))) {
                throw new InvalidParameterException(Yii::t('store', 'sale_on_shelves'));
            }
            StoreGoods::updateStatusByIds($storeGoodsIds, StoreGoods::STATUS_ON, new \MongoDate());
        } else if ($params['status'] == StoreGoods::STATUS_OFF && isset($params['onSaleTime']) && $params['onSaleTime'] !== '') {
            //check the store goods are not on shelves
            if (!empty(StoreGoods::getOnSaleByIds($storeGoodsIds, $accountId))) {
                throw new InvalidParameterException(Yii::t('store', 'sale_on_shelves'));
            }
            $onSaleTime = TimeUtil::ms2sTime($params['onSaleTime']);
            if (time() > $onSaleTime) {
                throw new InvalidParameterException(['onSaleTime' => \Yii::t('product', 'not_less_than_current')]);
            } else {
                StoreGoods::updateStatusByIds($storeGoodsIds, StoreGoods::STATUS_OFF, new \MongoDate($onSaleTime));
            }
        } else if ($params['status'] == StoreGoods::STATUS_OFF && (!isset($params['onSaleTime']) || $params['onSaleTime'] === '')) {
            StoreGoods::updateStatusByIds($storeGoodsIds, StoreGoods::STATUS_OFF, null, new \MongoDate());
        } else {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        return ['message' => 'OK', 'data' => null];
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        if (empty($params['status']) || !isset($params['price'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $storeGoods = StoreGoods::findByPk(new \MongoId($id));
        if (empty($storeGoods)) {
            throw new InvalidParameterException(Yii::t('store', 'invalid_goods_id'));
        }
        $storeGoods->pictures = empty($params['pictures']) ? $storeGoods->pictures : $params['pictures'];
        $price = floatval($params['price']);
        if ($price <= 0) {
            throw new InvalidParameterException(Yii::t('store', 'price_error'));
        }
        $storeGoods->price = $price;
        if ($params['status'] == StoreGoods::STATUS_ON) {
            $storeGoods->status = StoreGoods::STATUS_ON;
            $storeGoods->onSaleTime = new \MongoDate();
        } else if ($params['status'] == StoreGoods::STATUS_OFF && isset($params['onSaleTime']) && $params['onSaleTime'] !== '') {
            if (time() > TimeUtil::ms2sTime($params['onSaleTime'])) {
                throw new InvalidParameterException(['onSaleTime' => \Yii::t('product', 'not_less_than_current')]);
            } else {
                $storeGoods->status = StoreGoods::STATUS_OFF;
                $storeGoods->onSaleTime = new \MongoDate(TimeUtil::ms2sTime($params['onSaleTime']));
            }
        } else if ($params['status'] == StoreGoods::STATUS_OFF && (!isset($params['onSaleTime']) || $params['onSaleTime'] === '')) {
            $storeGoods->status = StoreGoods::STATUS_OFF;
            $storeGoods->onSaleTime = null;
            $storeGoods->offShelfTime = new \MongoDate();
        } else {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }

        if ($storeGoods->save(true)) {
            $storeGoods->_id = (string) $storeGoods->_id;
            return $storeGoods;
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    public function actionDelete($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        $idstrList = explode(',', $id);
        $ids = [];
        foreach ($idstrList as $perId) {
            $ids[] = new \MongoId($perId);
        }
        //check the store goods are not on shelves
        if (!empty(StoreGoods::getOnSaleByIds($ids, $accountId))) {
            throw new InvalidParameterException(Yii::t('store', 'delete_on_shelves'));
        }
        $condition = ['_id' => ['$in' => $ids]];
        if (StoreGoods::deleteAll($condition) == false) {
            throw new ServerErrorHttpException(Yii::t('common', 'delete_fail'));
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
