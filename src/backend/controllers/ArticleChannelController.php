<?php
namespace backend\controllers;

use backend\components\rest\RestController;
use backend\modules\microsite\models\ArticleChannel;
use backend\components\ActiveDataProvider;

class ArticleChannelController extends RestController
{
    public $modelClass = "backend\modules\microsite\models\ArticleChannel";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete']);
        return $actions;
    }
}
