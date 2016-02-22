<?php
namespace frontend\controllers;

use yii\web\Controller;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use backend\utils\StringUtil;
use backend\modules\microsite\models\Article;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\PageComponent;
use backend\utils\BrowserUtil;
use Yii;

/**
 * Site controller
 */
class MsiteController extends Controller
{
    public $page;
    const ARTICLE_PATH = 'article';
    const PAGE_PATH = 'page';
    const WIDGET_PATH = 'widget';
    const VENDOR_PATH = '/vendor/bower/';
    const MICROSITE_PATH = '/build/webapp/msite/';
    const NOT_FOUND_PAGE_PATH = '/mobile/common/404';

    /**
     * Render article page
     */
    public function actionArticle($id = '')
    {
        $id = new \MongoId($id);
        $article = Article::findByPk($id);
        if (empty($article) || empty($article->url)) {
            return $this->redirect(self::NOT_FOUND_PAGE_PATH);
        }
        $actionView = $this->getView();
        $sdk = Yii::$app->wechatSdk;
        $sdk->refererUrl = $sdk->refererDomain . 'msite/article/' . $id;
        $signPackage = $sdk->getSignPackage();
        $actionView->registerJsFile('https://res.wx.qq.com/open/js/jweixin-1.0.0.js');
        $actionView->registerJsFile(self::MICROSITE_PATH . 'article/index.js');
        $actionView->registerJsFile(self::VENDOR_PATH . 'moment/min/moment.min.js');
        $this->layout = self::ARTICLE_PATH;
        return $this->render(self::ARTICLE_PATH, ['signPackage' => $signPackage]);
    }

    /**
     * Render page, query string 's' indicates that get page record regardless of published status
     */
    public function actionPage($id = '')
    {
        $isPreview = \Yii::$app->request->get('s', 0);
        if (!empty($id)) {
            $id = new \MongoId($id);
            $page = Page::getPage($id, !!$isPreview);
            if (empty($page) || empty($page->url)) {
                $this->redirect(self::NOT_FOUND_PAGE_PATH);
            }
            if (empty($page['color'])) {
                $page['color'] = \Yii::$app->params['micrositeDefaultColor'];
            }
            if (empty($page->cpts) || $isPreview) {
                $cpts = PageComponent::getAllComponents($id);
                $page->cpts = ArrayHelper::toArray($cpts);
            }
            $sdk = Yii::$app->wechatSdk;
            $sdk->refererUrl = $sdk->refererDomain . 'msite/page/' . $id;
            $signPackage = $sdk->getSignPackage();
            $actionView = $this->getView();
            $actionView->registerJsFile('https://res.wx.qq.com/open/js/jweixin-1.0.0.js');
            $this->view->params['page'] = $page;
            $this->view->params['pageRGBColor'] = join(',', StringUtil::hex2rgb($page['color']));
            $this->layout = self::PAGE_PATH;
            $this->registerPageResource($isPreview);
            $params = [
                'signPackage' => $signPackage,
                'components' => $page->cpts,
                'page' => [
                    'title' => $page->title,
                    'desc' => $page->description,
                    'url' => $page->url,
                    'isCover' => $page->type === Page::TYPE_COVER
                ]
            ];

            if (BrowserUtil::isWeiboBrower() || BrowserUtil::isWeixinBrowser()) {
                $params['hideTitle'] = true;
            }

            return $this->render(self::PAGE_PATH, $params);
        } else {
            return $this->render(self::NOT_FOUND_PAGE_PATH);
        }
    }

    /**
     * Render widget, query string 't' indicates the name of the widget
     */
    public function actionWidget($id = '')
    {
        $widgetType = \Yii::$app->request->get('t', '');
        $params = \Yii::$app->params;
        $color = $params['micrositeDefaultColor'];
        $data = null;

        if (empty($widgetType)) {
            $this->redirect(self::NOT_FOUND_PAGE_PATH);
        }

        if (!empty($id)) {
            $id = new \MongoId($id);
            $widget = PageComponent::findByPk($id);
            if (empty($widget)) {
                $this->redirect(self::NOT_FOUND_PAGE_PATH);
            }
            if (!empty($widget['color'])) {
                $color = $widget['color'];
            }
            $data = $widget['jsonConfig'];
        }
        if (empty($data)) {
            $data = $params['micrositeDefaultConfig'][$widgetType];
        }
        $params = ['type' => $widgetType, 'color' => $color];
        $this->view->params = $params;
        $this->view->params['pageRGBColor'] = join(',', StringUtil::hex2rgb($color));
        $this->layout = self::WIDGET_PATH;
        $this->registerWidgetResource($widgetType);
        return $this->render(self::WIDGET_PATH . '/' . $widgetType, $data);
    }

    /**
     * Register Common JS files for page and widget action
     */
    private function registerBaseResource($actionView)
    {
        $venderFiles = ['zepto/zepto.min.js', 'zeptotouch/zepto-touch.min.js', 'lazyload/lazyload.min.js'];
        //Inject common vendor JS
        foreach ($venderFiles as $file) {
            $actionView->registerJsFile(self::VENDOR_PATH . $file);
        }
    }

    /**
     * Register JS files for page action
     */
    private function registerPageResource($isPreview)
    {
        //JS dependencies
        $venderFiles = ['lib.flexible/flexible.js', 'photoswipe/dist/klass.min.js', 'photoswipe/dist/code.photoswipe-3.0.5.min.js', 'swipe/swipe.js'];
        $customizedFiles = ['page/article.js', 'page/album.js', 'page/slide.js', 'page/questionnaire.js'];
        $actionView = $this->getView();
        // //Inject statistic JS
        // if (!$isPreview) {
        //     $shortenUrlKey = \Yii::$app->urlService->shortenUrl2Key($page->url);
        //     $actionView->registerJsFile(\Yii::$app->urlService->shortUrlDomain . "/v/$shortenUrlKey");
        // }
        $this->registerBaseResource($actionView);
        //Inject vendor JS
        foreach ($venderFiles as $file) {
            $actionView->registerJsFile(self::VENDOR_PATH . $file);
        }
        //Inject customized JS
        foreach ($customizedFiles as $file) {
            $actionView->registerJsFile(self::MICROSITE_PATH . $file);
        }
        $actionView->registerJsFile(self::MICROSITE_PATH . 'page/index.js');
    }

    /**
     * Register JS files for widget action
     */
    private function registerWidgetResource($widgetType = '')
    {
        $widgetMap = [
            'articles' => [
                'customized' => ['page/article.js']
            ],
            'album' => [
                'vendor' => ['photoswipe/dist/klass.min.js', 'photoswipe/dist/code.photoswipe-3.0.5.min.js', 'swipe/swipe.js'],
                'customized' => ['page/album.js']
            ],
            'cover1' => [
                'vendor' => ['swipe/swipe.js'],
                'customized' => ['page/slide.js']
            ],
            'slide' => [
                'vendor' => ['swipe/swipe.js'],
                'customized' => ['page/slide.js']
            ],
            'questionnaire' => [
                'customized' => ['page/questionnaire.js']
            ]
        ];
        $actionView = $this->getView();
        $this->registerBaseResource($actionView);
        //Inject specific JS files for widget
        if (array_key_exists($widgetType, $widgetMap)) {
            $jsFileMap = $widgetMap[$widgetType];
            if (!empty($jsFileMap['vendor'])) {
                $jsFiles = $jsFileMap['vendor'];
                if (!empty($jsFiles)) {
                    foreach ($jsFiles as $file) {
                        $actionView->registerJsFile(self::VENDOR_PATH . $file);
                    }
                }
            }
            if (!empty($jsFileMap['customized'])) {
                $jsFiles = $jsFileMap['customized'];
                if (!empty($jsFiles)) {
                    foreach ($jsFiles as $file) {
                        $actionView->registerJsFile(self::MICROSITE_PATH . $file);
                    }
                }
            }
        }
        $actionView->registerJsFile(self::MICROSITE_PATH . 'page/index.js');
    }
}
