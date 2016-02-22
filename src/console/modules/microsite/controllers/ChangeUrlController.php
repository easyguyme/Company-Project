<?php
namespace console\modules\microsite\controllers;

use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\Article;
use yii\console\Controller;
use Yii;

/**
 * Change the 'microsite' to 'msite' and generate the new url
 **/
class ChangeUrlController extends Controller
{
    /**
     * change page url
     * change article url
     */
    public function actionIndex()
    {
        $this->actionPage();
        $this->actionArticle();
    }

    public function actionPage()
    {
        $allPages = Page::findAll([]);
        if ($allPages) {
            foreach ($allPages as $page) {
                if (isset($page->url) && !empty($page->url))  {
                    $url = $page->url;
                    $newUrl = ereg_replace('microsite', 'msite', $url);
                    $shortenResult = Yii::$app->urlService->shortenUrl($newUrl);
                    $shortUrl = $shortenResult['Short'];
                    $page->url = $newUrl;
                    $page->shortUrl = $shortUrl;
                    if (!$page->update()) {
                        Yii::error('Update page ' . (string) $page->_id . ' fail', 'application');
                    }
                }
            }
        }
    }

    public function actionArticle()
    {
        $allArticles = Article::findAll([]);
        if ($allArticles) {
            foreach ($allArticles as $article) {
                if (isset($article->url) && !empty($article->url)) {
                    $url = $article->url;
                    $statResult = Yii::$app->urlService->statistics($url);
                    $longUrl = $statResult['Long'];
                    $newUrl = ereg_replace('microsite', 'msite', $longUrl);
                    $shortenResult = Yii::$app->urlService->shortenUrl($newUrl);
                    $shortUrl = $shortenResult['Short'];
                    $article->url = $shortUrl;
                    if (!$article->update(false)) {
                        Yii::error('Update article ' . (string) $article->_id . ' fail', 'application');
                    }
                }
            }
        }
    }
}
