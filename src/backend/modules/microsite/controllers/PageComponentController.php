<?php

namespace backend\modules\microsite\controllers;

use backend\modules\microsite\models\PageComponent;
use backend\modules\microsite\models\Page;
use yii\web\ServerErrorHttpException;
use backend\components\BaseModel;
use yii\web\BadRequestHttpException;
use Yii;

class PageComponentController extends BaseController
{
    public $modelClass = "backend\modules\microsite\models\PageComponent";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete']);
        return $actions;
    }

    /**
     * Query page component list
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/microsite/page-components
     * <b>Summary: </b>This api is for query page component list
     *
     * <b>Request Params</b>:<br/>
     *     pageId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Example: </b>
     * <pre>
     *  [
     *      {
     *          "id": "54f05829e9c2fbfa038b4585",
     *          "parentId": "54f05829e9c2fbfa038b4585",
     *          "pageId": "54f05829e9c2fbfa038b4585",
     *          "name": "articles",
     *          "jsonConfig": [],
     *          "color": "#6ab3f7",
     *          "order": 0,
     *          "tabIndex": null,
     *          "tabs": null
     *      },
     *      {
     *          "id": "54f0581fe9c2fbc3168b4586",
     *          "parentId": "54f0581fe9c2fbc3168b4586",
     *          "pageId": "54f0581fe9c2fbc3168b4586",
     *          "name": "tab",
     *          "jsonConfig": [
     *              "tabs": [
     *                  {
     *                      "name": "Tab1",
     *                      "cpts": [
     *                          {
     *                              "id": "54f05831e9c2fbad3d8b457e",
     *                              "parentId": "54f05831e9c2fbad3d8b457e",
     *                              "pageId": "54f05831e9c2fbad3d8b457e",
     *                              "name": "title",
     *                              "jsonConfig": [],
     *                              "color": "#6ab3f7",
     *                              "order": 0,
     *                              "tabIndex": 0,
     *                              "tabs": null
     *                          }
     *                      ],
     *                      "active": true
     *                  },
     *                  {
     *                      "name": "Tab2",
     *                      "cpts": [
     *                          {
     *                              "id": "54f05b73e9c2fbad3d8b457f",
     *                              "parentId": "54f05b73e9c2fbad3d8b457f",
     *                              "pageId": "54f05b73e9c2fbad3d8b457f",
     *                              "name": "album",
     *                              "jsonConfig": [],
     *                              "color": "#6ab3f7",
     *                              "order": 0,
     *                              "tabIndex": 1,
     *                              "tabs": null
     *                          }
     *                      ],
     *                      "active": false
     *                  }
     *              ]
     *          ],
     *          "color": "#6ab3f7",
     *          "order": 1,
     *          "tabIndex": null
     *      }
     *  ]
     * </pre>
     */
    public function actionIndex()
    {
        $pageId = $this->getQuery('pageId');
        if (empty($pageId)) {
            throw new BadRequestHttpException(\Yii::t('common', 'parameters_missing'));
        }

        $pageComponents = PageComponent::getAllComponents(new \MongoId($pageId));
        return $pageComponents;
    }

    public function actionCreate()
    {
        $pageComponent = new PageComponent(['scenario' => BaseModel::SCENARIO_CREATE]);
        $params = $this->getParams();
        $tabId = $this->getParams('tabId');
        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;
        unset($params['tabId']);

        $pageComponent->load($params, '');
        $pageComponent->jsonConfig = Yii::$app->params['micrositeDefaultConfig'][$pageComponent->name];

        if ($pageComponent->validate() && $pageComponent->save()) {
            $pageId = $pageComponent->pageId;
            $order = $pageComponent->order;
            $pageComponentId = $pageComponent->_id;

            $condition = ['order' => ['$gte' => $order], '_id' => ['$ne' => $pageComponentId]];
            $inc = 1;
            $tabIndex = $pageComponent->tabIndex;
            $this->_updateOrder($condition, $inc, $pageId, $tabId, $tabIndex);

        } else {
            throw new ServerErrorHttpException(\Yii::t('common', 'save_fail'));
        }

        return $pageComponent;
    }

    /**
     * Update component order
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/microsite/page-component/order/{id}
     * <b>Summary: </b>This api is for Update component order
     *
     * <b>Request Params</b>:<br/>
     *     newOrder: int<br/>
     *     tabId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Example: </b>
     * <pre>
     *  {'message' : 'OK'}
     * </pre>
     */
    public function actionOrder($id)
    {
        $pageComponentId = new \MongoId($id);
        $newOrder = $this->getParams('newOrder');
        $tabId = $this->getParams('tabId');
        $pageComponent = PageComponent::findByPk($pageComponentId);

        if (empty($pageComponent)) {
            throw new ServerErrorHttpException(\Yii::t('common', 'data_error'));
        }
        $oldOrder = $pageComponent->order;
        $pageId = $pageComponent->pageId;
        $tabIndex = $pageComponent->tabIndex;

        $pageComponent->order = $newOrder;

        if ($pageComponent->save(true, ['order'])) {
            if ($newOrder > $oldOrder) {
                $inc = -1;
                $condition = ['order' => ['$lte' => $newOrder , '$gte' => $oldOrder], '_id' => ['$ne' => $pageComponentId]];
            } else {
                $inc = 1;
                $condition = ['order' => ['$lte' => $oldOrder , '$gte' => $newOrder], '_id' => ['$ne' => $pageComponentId]];
            }
            $this->_updateOrder($condition, $inc, $pageId, $tabId, $tabIndex);
        } else {
            throw new ServerErrorHttpException(\Yii::t('common', 'update_fail'));
        }

        return ['message' => 'OK'];
    }

    /**
     * Delete page component
     *
     * <b>Request Type: </b>DELETE<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/microsite/page-component/{id}
     * <b>Summary: </b>This api is for delete page component
     *
     * <b>Response Example: </b>
     * <pre>
     *  {'message' : 'OK'}
     * </pre>
     */
    public function actionDelete($id)
    {
        $pageComponentId = new \MongoId($id);
        $pageComponent = PageComponent::findByPk($pageComponentId);

        if (empty($pageComponent)) {
            throw new ServerErrorHttpException(\Yii::t('common', 'data_error'));
        }

        $result = PageComponent::deleteAll(['$or' => [['_id' => $pageComponentId], ['parentId' => $pageComponentId]]]);

        if ($result) {
            $tabId = $pageComponent->pageId === $pageComponent->parentId ? null : $pageComponent->parentId;
            $inc = -1;
            $condition = ['order' => ['$gt' => $pageComponent->order]];
            $this->_updateOrder($condition, $inc, $pageComponent->pageId, $tabId, $pageComponent->tabIndex);
        } else {
            throw new ServerErrorHttpException(\Yii::t('common', 'delete_fail'));
        }

        return ['message' => 'OK'];
    }

    private function _updateOrder($condition, $inc, $pageId, $tabId = null, $tabIndex = null)
    {
        if (empty($tabId)) {
            PageComponent::updateAll(
                ['$inc' => ['order' => $inc]],
                array_merge($condition, ['parentId' => $pageId])
            );
        } else {
            PageComponent::updateAll(
                ['$inc' => ['order' => $inc]],
                array_merge($condition, ['parentId' => new \MongoId($tabId), 'tabIndex' => $tabIndex])
            );
        }
    }
}
