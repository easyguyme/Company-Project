<?php
namespace backend\controllers;

use backend\components\rest\RestController;

class ProductInfoController extends RestController
{
    public $modelClass = "backend\modules\product\models\ProductInfo";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['update'], $actions['delete']);
        return $actions;
    }
}
