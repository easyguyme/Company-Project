<?php
namespace backend\components\extservice\models;

use backend\modules\product\models\Product as ModelProduct;
use backend\modules\product\models\ProductInfo;

class Product extends BaseComponent
{
    /**
     * Get by Ids
     * @param array $ids
     * @return array
     */
    public function getByIds($ids)
    {
        return ModelProduct::getByIds($ids);
    }

    public function getById($id)
    {
        return ModelProduct::findOne(['_id' => $id]);
    }

    public function getSpecifications($id)
    {
        $product = ModelProduct::find()->select(['specifications'])->where(['_id' => $id])->one();
        if (!empty($product)) {
            return $product->specifications;
        } else {
            return [];
        }
    }

    public function getProductInfo($id)
    {
        $productInfo = ProductInfo::findByPk($id);
        if (!empty($productInfo)) {
            return $productInfo->intro;
        } else {
            return '';
        }
    }
}
