<?php
namespace backend\modules\channel\controllers;

use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\utils\TimeUtil;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use Yii;

class MenuController extends BaseController
{

    /**
     * Query defined menus.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/menus<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for query menus
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried meuns detail information<br/>
     *     _meta: object, page information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "items": [
     *      {
     *          "name": "去踢球",
     *          "type": "CLICK",
     *          "subMenus": [
     *              {
     *                  "msgType": "TEXT",
     *                  "content": "hello,world",
     *                  "name": "去踢球",
     *                  "type": "VIEW"
     *              },
     *              {
     *                  "msgType": "NEWS",
     *                  "content": {
     *                      "articles": [
     *                          {
     *                              "title": "part1",
     *                              "description": "description2",
     *                              "picUrl": "http://www.baidu.com",
     *                              "sourceUrl": "http://www.baidu.com"
     *                          },
     *                          {
     *                              "title": "part1",
     *                              "description": "description2",
     *                              "picUrl": "http://www.baidu.com",
     *                              "sourceUrl": "http://www.baidu.com"
     *                          }
     *                      ]
     *                  },
     *                  "name": "去踢球",
     *                  "type": "VIEW"
     *              }
     *          ]
     *      },
     *      {
     *          "name": "去踢球",
     *          "type": "CLICK",
     *          "subMenus": [
     *              {
     *                  "msgType": "TEXT",
     *                  "content": "hello,world",
     *                  "name": "去踢球",
     *                  "keycode": "code1",
     *                  "type": "VIEW"
     *              },
     *              {
     *                  "msgType": "URL",
     *                  "content": "http://www.baidu.com",
     *                  "name": "去踢球",
     *                  "keycode": "code1",
     *                  "type": "VIEW"
     *              }
     *          ]
     *      }
     *   ],
     *   "isEnabledHelpDesk": true,
     *   "_meta": {
     *      "totalCount": 1,
     *      "pageCount": 1,
     *      "currentPage": 1,
     *      "perPage": 20
     *   }
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $query = $this->getQuery();

        $channelId = $this->getChannelId();
        $accountId = $this->getAccountId();
        unset($query['channelId']);
        $keycodes = Yii::$app->channelMenu->getAllKeycode($channelId, $accountId);
        $menu = Yii::$app->weConnect->getMenu($channelId, $keycodes);
        return ['items' => $menu, 'keycodes' => $keycodes];
    }

    /**
     * Create menu
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/menus<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create/update menu.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     menu.name: string<br/>
     *     menu.keycode: string<br/>
     *     menu.type: string, VIEW or CLICK<br/>
     *     menu.subMenus.name: string<br/>
     *     menu.subMenus.type: string<br/>
     *     menu.subMenus.msgType: TEXT or NEWS<br/>
     *     menu.subMenus.content: string, if TEXT<br/>
     *     menu.subMenus.content.articles: array, if NEWS<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Request Example</b>:<br/>
     * <pre>
     * {
     *  "channelId": "5473ffe7db7c7c2f0bee5c71",
     *  "menu": [
     *      {
     *          ...
     *      },
     *      {
     *          "name": "去踢球",
     *          "type": "CLICK",
     *          "subMenus": [
     *              {
     *                  "name": "menu1-sub1",
     *                  "type": "VIEW",
     *                  "msgType": "TEXT",
     *                  "content": "hello world"
     *              },
     *              {
     *                  "name": "menu1-sub2",
     *                  "type": "VIEW",
     *                  "msgType": "URL",
     *                  "content": "http://www.baidu.com"
     *              },
     *              {
     *                  "name": "menu1-sub3",
     *                  "type": "VIEW",
     *                  "msgType": "NEWS",
     *                  "content": {
     *                      "articles": [
     *                          {
     *                              "title": "新闻",
     *                              "description": "APEC会议举行第三天",
     *                              "sourceUrl": "http://www.baidu.com",
     *                              "picUrl": "http://www.baidu.com/image.jpg"
     *                          },
     *                          {
     *                              "title": "新闻",
     *                              "description": "APEC会议举行第三天",
     *                              "sourceUrl": "http://www.baidu.com",
     *                              "picUrl": "http://www.baidu.com/image.jpg"
     *                          }
     *                      ]
     *                  }
     *              }
     *          ]
     *      },
     *      {
     *          ...
     *      }
     *  ]
     * }
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "message": "OK"
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $menu = $this->getParams();
        $channelId = $this->getChannelId();
        $accountId = $this->getAccountId();

        unset($menu['channelId']);

        $actions = Yii::$app->channelMenu->getMenuActions($channelId, $accountId, true);
        $result = Yii::$app->weConnect->createMenu($channelId, $menu['menu'], $actions);

        if ($result) {
            $conditon = ['accountId' => $accountId, 'channels.id' => $channelId];
            $helpDeskSetting = HelpDeskSetting::findOne($conditon);
            if (!empty($helpDeskSetting)) {
                $isSet = Yii::$app->weConnect->isSetHelpDesk($menu['menu']);
                $channelResult = HelpDeskSetting::updateAll(
                    ['$set' => ['channels.$.isSet' => $isSet]],
                    $conditon
                );
                if (!$channelResult) {
                    throw new ServerErrorHttpException('Set menu channel status fail.');
                }
            }
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException(\Yii::t('channel', 'menu_error'));
        }
    }

    /**
     * Publish menu
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/menu/publish<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to publish menu.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "message": "OK"
     * }
     * </pre>
     */
    public function actionPublish()
    {
        $menu = $this->getParams();
        $channelId = $this->getChannelId();
        $accountId = $this->getAccountId();
        $ignoreTypes = Yii::$app->channelMenu->getMenuNoContentActions($channelId, $accountId);
        $menu['action'] = 'publish';
        $result = Yii::$app->weConnect->changeMenuPublish($channelId, $menu, $ignoreTypes);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException(\Yii::t('channel', 'menu_publish_fail'));
        }
    }

    /**
     * Unpublish menu
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/menu/unpublish<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to unpublish menu.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "message": "OK"
     * }
     * </pre>
     */
    public function actionUnpublish()
    {
        $menu = $this->getParams();
        $channelId = $this->getChannelId();
        $menu['action'] = 'unpublish';
        $result = Yii::$app->weConnect->changeMenuPublish($channelId, $menu);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Unpublish menu fail.');
        }
    }

    public function actionStatistics()
    {
        $channelId = $this->getChannelId();
        $startDate = $this->getQuery("from");
        $endDate = $this->getQuery("to");
        $menuId = $this->getQuery("menuId");

        $destResult = [];
        $data = Yii::$app->weConnect->menuStatistics($channelId, $menuId, $startDate, $endDate);

        if (!empty($data)) {
            $destResult = $this->formateResponseData($data, ['count' => 'count'], $startDate, $endDate);
        }

        return $destResult;
    }
}
