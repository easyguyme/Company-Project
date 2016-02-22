<?php
namespace backend\modules\product\job;

use backend\utils\LogUtil;
use backend\modules\product\models\Product;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\GoodsExchangeLog;

class UpdateProductLog
{
    public function perform()
    {
        $args = $this->args;

        if (empty($args['productId'])) {
            LogUtil::error(['message' => 'Missing productId when update product info'], 'resque');
            return false;
        }

        $product = Product::findByPk($args['productId']);
        if (empty($product)) {
            LogUtil::error(['message' => 'productId is invalid', 'productId' => $args['productId']]);
            return false;
        }

        CampaignLog::updateAll(['productName' => $product->name, 'sku' => $product->sku], ['productId' => $product->_id]);

        GoodsExchangeLog::updateAll(['goods.$.productName' => $product->name, 'goods.$.sku' => $product->sku], ['goods.productId' => $product->_id]);

        return true;
    }
}
