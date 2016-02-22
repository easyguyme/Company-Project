<?php

namespace backend\modules\microsite\controllers;

use Yii;
use backend\modules\microsite\models\Page;
use backend\modules\microsite\models\PageComponent;
use backend\models\Token;
use backend\models\User;
use yii\web\ServerErrorHttpException;
use yii\helpers\ArrayHelper;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;

class PageController extends BaseController
{
    public $modelClass = "backend\modules\microsite\models\Page";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['view'], $actions['delete']);
        return $actions;
    }

    /**
     * The first step of creating page
     * Use the createBasic scenario
     **/
    public function actionCreate()
    {
        $params = $this->getParams();
        $accesstoken = $this->getAccessToken();
        $token = Token::getToken($accesstoken);
        $page = new Page(['scenario' => 'createBasic']);
        $page->attributes = $params;
        $page->_id = new \MongoId();
        $page->accountId = $token->accountId;
        $userId = $token->userId;
        $user = User::findByPk($userId);
        $page->creator = ['id' => $userId, 'name' => $user->name];
        $page->url = Yii::$app->request->hostinfo . '/msite/page/' . $page->_id;
        $shortUrl = Yii::$app->urlService->shortenUrl($page->url);
        $page->shortUrl = $shortUrl['Short'];

        if ($page->validate()) {
            // all inputs are valid
            if ($page->save()) {
                return $page;
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            $errors = array_keys($page->errors);
            throw new InvalidParameterException([$errors[0] => Yii::t("microSite", $errors[0] . '_field_not_empty')]);
        }
    }

    public function actionDelete($id)
    {
        $pageId = new \MongoId($id);
        $page = Page::findByPk($pageId);
        if (empty($page)) {
            throw new BadRequestHttpException('Failed to find page');
        }
        if (!$page->deletable) {
            throw new BadRequestHttpException(\Yii::t('microsite', 'page_cannt_delete'));
        }

        if ($page->delete()) {
            $result = PageComponent::deleteAll(['pageId' => $pageId]);
        } else {
            throw new ServerErrorHttpException('Delete fail');
        }

        return ['message' => 'OK'];
    }

    /**
     * Update page color.
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/microsite/page/color/{id}
     * <b>Summary: </b>This api is for update page color
     *
     * <b>Request Params</b>:<br/>
     *     color: string<br/>
     *     <br/><br/>
     *
     * <b>Response Example: </b>
     * <pre>
     *  {
     *      "message" : "OK"
     *  }
     * </pre>
     */
    public function actionColor($id)
    {
        $pageId = new \MongoId($id);
        $page = Page::findByPk($pageId);
        $color = $this->getParams('color');
        if (empty($color)) {
            throw new BadRequestHttpException('Missing color');
        }

        $page->color = $color;
        if ($page->save(true, ['color'])) {
            PageComponent::updateAll(['$set' => ['color' => $color]], ['pageId' => $pageId]);
        } else {
            throw new ServerErrorHttpException('update fail');
        }

        return ['message' => 'ok'];
    }

    /**
     * The second step of creating page
     * Add the components to the page
     * Use the createComponents scenario
     *
     * @param  string $id page id
     * @return array
     * @throws ServerErrorHttpException
     */
    public function actionAddComponents($id)
    {
        $params = $this->getParams();
        $pageId = new \MongoId($id);
        $page = Page::findByPk($pageId);
        $page->scenario = 'addComponents';
        $page->attributes = $params;

        if ($page->validate()) {
            // all inputs are valid
            if ($page->save()) {
                return ['_id' => $page->_id . ''];
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            return $page->errors;
        }
    }

    /**
     * View a page with statistics data
     * @param  string $id page id
     * @return array
     * @throws ServerErrorHttpException
     */
    public function actionView($id)
    {
        $pageId = new \MongoId($id);
        $startDate = $this->getQuery('from');
        $endDate = $this->getQuery('to');
        $page = Page::getPage($pageId, true);

        if (empty($page)) {
            throw new ServerErrorHttpException(Yii::t('microSite', 'page_not_exist'));
        }

        $pageArr = $page->toArray();

        // find the statistics data from url service
        if (isset($page->shortUrl) && !empty($page->shortUrl) && strstr($page->shortUrl, Yii::$app->urlService->shortUrlDomain)) {
            // build a url with 'from' and 'to' params
            $url = Yii::$app->curl->buildUrl($page->shortUrl, ['from' => $startDate, 'to' => $endDate]);
            $pageArr['statistics'] = Yii::$app->urlService->statistics($url);
        }

        return $pageArr;
    }

    /**
     * View a page with statistics data
     * @param  string $id page id
     */
    public function actionPublish($id)
    {
        $pageId = new \MongoId($id);
        $page = Page::findByPk($pageId);

        if (empty($page)) {
            throw new ServerErrorHttpException(Yii::t('microSite', 'page_not_exist'));
        }

        $cpts = PageComponent::getAllComponents($pageId);
        $page->cpts = ArrayHelper::toArray($cpts);
        $page->isFinished = true;

        if ($page->validate()) {
            // all inputs are valid
            if ($page->save()) {
                return ['message' => 'ok'];
            } else {
                throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
            }
        } else {
            // valid fail, return errors
            return $page->errors;
        }
    }
}
