<?php
namespace console\modules\management\controllers;

use Yii;
use MongoId;
use backend\models\SensitiveOperation;
use yii\console\Controller;

/**
 * do something to support klp
 **/
class KlpController extends Controller
{
    public function actionAuth($accountId)
    {
        if (!empty($accountId)) {
            $accountId = new MongoId($accountId);
            $condition = ['accountId' => $accountId];
            $attributes = ['$pull' => ['states' => 'product-goods']];
            SensitiveOperation::updateAll($attributes, $condition);
            $operation = new SensitiveOperation();
            $operation->name = 'klp default';
            $operation->users = [];
            $operation->states = [
                'member-setting',
                'product-edit-promotion',
                'product-edit-promotion-{id}',
                'product-create-goods',
                'product-setting'
            ];
            $operation->actions = [
                'product/campaign/update',
                'product/campaign/create',
                'product/campaign/delete',
                'mall/goods/create',
                'mall/goods/update-goods-status',
                'mall/goods/delete',
                'product/product-category/create',
                'product/product-category/update',
                'product/product-category/delete'
            ];
            $operation->isActivated = true;
            $operation->accountId = $accountId;
            $operation->save();
            echo 'klp auth successfully' . PHP_EOL;
        }
    }
}
