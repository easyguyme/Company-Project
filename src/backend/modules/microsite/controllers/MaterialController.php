<?php

namespace backend\modules\microsite\controllers;

use Yii;
use backend\components\Controller;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\Article;
use backend\utils\MongodbUtil;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;

class MaterialController extends Controller
{
    /**
     * Search page and article by url(page: shortUrl, article: url) and name(page: title, article: name)
     * 1. Get data from page collection and article collection
     * 2. Merge page and article
     * 3. Sort by createdAt
     * 4. Format and return
     */
    public function actionIndex()
    {
        $query = $this->getQuery();
        $pageSize = $this->getQuery('per-page', 10);
        $accountId = $this->getAccountId();

        $timeFrom = null;
        $searchKey = (isset($query['searchKey']) && $query['searchKey'] !== '') ? $query['searchKey'] : null;
        $searchKey = urldecode($searchKey);
        if (isset($query['timeFrom']) && $query['timeFrom'] !== '') {
            $timeFrom = MongodbUtil::msTimetamp2MongoDate($query['timeFrom']);
        }
        //Get data from page collection and article collection
        $pages = Page::searchByTitleAndUrl($accountId, $pageSize, $searchKey, $timeFrom);
        $articles = Article::searchByNameAndUrl($accountId, $pageSize, $searchKey, $timeFrom);
        //Merge page and article and sort
        $pageAndArticle = array_merge($pages, $articles);
        usort($pageAndArticle, 'self::cmpCreatedAt');

        $result = [];
        $items = [];
        $result['timeFrom'] = null;
        $rowIndex = 0;
        //format data
        foreach ($pageAndArticle as $item) {
            $items[] = [
                'id' => (string) $item['_id'],
                'title' => !isset($item['title']) ? $item['name'] : $item['title'],
                'url' => empty($item['shortUrl']) ? $item['url'] : $item['shortUrl'],
                'type' => !isset($item['title']) ? 'article' : 'page',
            ];
            $result['timeFrom'] = MongodbUtil::MongoDate2msTimeStamp($item['createdAt']);
            $rowIndex++;
            if ($rowIndex >= $pageSize) {
                break;
            }
        }
        $result['items'] = $items;

        return $result;
    }

    public static function cmpCreatedAt($firstPageAndArticle, $secondPageAndArticle)
    {
        $firstCreatedAt = MongodbUtil::MongoDate2msTimeStamp($firstPageAndArticle['createdAt']);
        $secondCreatedAt = MongodbUtil::MongoDate2msTimeStamp($secondPageAndArticle['createdAt']);
        if ($firstCreatedAt == $secondCreatedAt) {
            return 0;
        }
        return ($firstCreatedAt < $secondCreatedAt) ? 1 : -1;
    }

    public function actionTitle()
    {
        $url = $this->getQuery('url');
        if (empty($url)) {
            throw BadRequestHttpException('common', 'parameters_missing');
        }
        $url = urldecode($url);
        $page = Page::getByShortUrl($url);
        if (!empty($page->title)) {
            return ['title' => $page->title];
        }

        $article = Article::getByUrl($url);
        return ['title' => empty($article->name) ? '' : $article->name];
    }
}
