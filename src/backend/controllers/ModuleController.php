<?php
namespace backend\controllers;

use backend\components\Controller;
use Yii;

class ModuleController extends Controller
{
    public function actionList()
    {
        return Yii::$app->extModule->getList();
    }
}
