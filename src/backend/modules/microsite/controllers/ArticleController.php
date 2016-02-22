<?php

namespace backend\modules\microsite\controllers;

use Yii;
use backend\modules\microsite\models\Article;
use yii\helpers\Json;
use backend\components\ActiveDataProvider;
use yii\web\ServerErrorHttpException;
use backend\models\Account;
use backend\exceptions\InvalidParameterException;
use backend\utils\UrlUtil;

class ArticleController extends BaseController
{
    public $modelClass = "backend\modules\microsite\models\Article";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create']);
        return $actions;
    }

    /**
     * Get article list
     */
    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        return Article::search($accountId, $params);
    }

    /**
     * Get the article with the statistics
     * @author HarrySun
     */
    public function actionView($id)
    {
        $articleId = new \MongoId($id);
        $dateFrom = $this->getQuery('from');
        $dateTo = $this->getQuery('to');
        $article = Article::findByPk($articleId);

        if (empty($article)) {
            throw new ServerErrorHttpException("Article id is wrong");
        }

        if (!isset($article->url) || empty($article->url)) {
            throw new ServerErrorHttpException("Article url is empty");
        }

        $url = Yii::$app->curl->buildUrl($article->url, ['from' => $dateFrom, 'to' => $dateTo]);
        $articleArr = $article->toArray();
        $articleArr['statistics'] = Yii::$app->urlService->statistics($url);
        return $articleArr;
    }

    /**
     * Create article
     * @author Devin Jin
     */
    public function actionCreate()
    {
        $attributes = $this->getParams();
        $article = new Article;
        $article->load($attributes, '');

        //short url generation
        $article->_id = new \MongoId();
        $originUrl = UrlUtil::getDomain() . '/msite/article/' . $article->_id;
        $urlArr = Yii::$app->urlService->shortenUrl($originUrl);
        $article->url = $urlArr['Short'];

        $accountId = $this->getAccountId();
        $article->accountId = $accountId;

        if (false === $article->save()) {
            $errors = array_keys($article->errors);
            if ($errors[0] == 'name') {
                $errors[0] = 'title';
            }
            throw new InvalidParameterException([$errors[0] => Yii::t("microSite", $errors[0] . '_field_not_empty')]);
        } else {
            return $article;
        }
    }
}
