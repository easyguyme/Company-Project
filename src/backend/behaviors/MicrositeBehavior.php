<?php
namespace backend\behaviors;

use backend\modules\microsite\models\ArticleChannel;
use yii\base\Behavior;
use yii\web\ServerErrorHttpException;
use Yii;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\PageComponent;

/**
 * Class file for article behavior
 * Contains the common functions for article related feature
 * @author Devin Jin
 */
class MicrositeBehavior extends Behavior
{
    /**
     * Create default article channel
     * @param  \MongoId $accountId
     * @throws ServerErrorHttpException when save the record for default article channel failed.
     */
    public function createDefaultChannel(\MongoId $accountId)
    {
        $defaultChannel = ArticleChannel::getDefault($accountId);

        if (empty($defaultChannel)) {
            $channel = new ArticleChannel;
            $channel->name = Yii::t('microSite', 'default_channel');
            $channel->fields = [];
            $channel->isDefault = true;
            $channel->accountId = $accountId;

            if (!$channel->save()) {
                throw new ServerErrorHttpException("create default article channel failed");
            }
        }
    }

    /**
     * Create default page cover
     * @param \MongoId $accountId
     * @throws ServerErrorHttpException
     */
    public function createDefaultPageCover(\MongoId $accountId)
    {
        $page = new Page(['scenario' => 'createBasic']);

        $defaultCoverPage = Yii::$app->params['default_cover_page'];
        $page->load($defaultCoverPage, '');
        $page->_id = new \MongoId();
        $page->accountId = $accountId;
        $page->url = Yii::$app->request->hostinfo . '/msite/page/' . $page->_id;
        $shortUrl = Yii::$app->urlService->shortenUrl($page->url);
        $page->shortUrl = $shortUrl['Short'];

        if ($page->save()) {
            $pageComponent = new PageComponent(['scenario' => PageComponent::SCENARIO_CREATE]);

            $defaultCoverPageCompnent = Yii::$app->params['default_cover_pagecomponent'];
            $pageComponent->load($defaultCoverPageCompnent, '');
            $pageComponent->pageId = $page->_id;
            $pageComponent->parentId = $page->_id;
            $pageComponent->accountId = $accountId;
            $pageComponent->jsonConfig = Yii::$app->params['micrositeDefaultConfig'][$pageComponent->name];

            $pageComponent->save();
        } else {
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        }
    }

    /**
     * Create default data for article
     * @param  \MongoId $accountId
     * @throws ServerErrorHttpException when save the record for default article channel failed.
     */
    public function createDefaultData(\MongoId $accountId)
    {
        $this->createDefaultChannel($accountId);
        $this->createDefaultPageCover($accountId);
    }
}
