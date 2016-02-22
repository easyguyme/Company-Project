<?php
namespace backend\controllers;

use backend\components\rest\RestController;
use backend\modules\microsite\models\Article;
use backend\components\ActiveDataProvider;

class ArticleController extends RestController
{
    public $modelClass = "backend\modules\microsite\models\Article";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $query = Article::find();
        $channelId = $this->getQuery('channel');
        $where = ['isDeleted' => false, 'channel' => new \MongoId($channelId)];

        $orderBy = $this->getQuery('orderBy');

        if (!empty($orderBy)) {
            $orderBy = Json::decode($orderBy, true);

            foreach ($orderBy as $key => $value) {
                if ('asc' === strtolower($value)) {
                    $orderBy[$key] = SORT_ASC;
                } else {
                    $orderBy[$key] = SORT_DESC;
                }
            }
        } else {
            $orderBy = ['createdAt' => SORT_DESC];
        }

        return new ActiveDataProvider([
            'query' => $query->where($where)->orderBy($orderBy),
        ]);
    }
}
