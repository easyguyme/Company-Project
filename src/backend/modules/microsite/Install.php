<?php
namespace backend\modules\microsite;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\modules\microsite\models\ArticleChannel;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\PageComponent;
use backend\components\BaseInstall;
use backend\utils\UrlUtil;

class Install extends BaseInstall
{
    /**
     * Create default article channel
     * @param  \MongoId $accountId
     * @throws ServerErrorHttpException when save the record for default article channel failed.
     */
    private function _createDefaultChannel(\MongoId $accountId)
    {
        $defaultChannel = ArticleChannel::getDefault($accountId);

        if (empty($defaultChannel)) {
            $channel = new ArticleChannel;
            $channel->name = 'default_channel';
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
    private function _createDefaultPageCover(\MongoId $accountId)
    {
        $page = Page::getDefaultCover($accountId);
        if (empty($page)) {
            $page = new Page(['scenario' => 'createBasic']);

            $defaultCoverPage = Yii::$app->params['default_cover_page'];
            $page->load($defaultCoverPage, '');
            $page->_id = new \MongoId();
            $page->accountId = $accountId;
            $page->url = UrlUtil::getDomain() . '/msite/page/' . $page->_id;
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
    }

    public function run($accountId)
    {
        parent::run($accountId);

        $this->_createDefaultChannel($accountId);
        $this->_createDefaultPageCover($accountId);
    }
}
