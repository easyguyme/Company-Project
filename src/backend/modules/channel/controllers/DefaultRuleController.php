<?php
namespace backend\modules\channel\controllers;

use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class DefaultRuleController extends BaseController
{

    /**
     * Query all default rules
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/default-rules<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting all default rules
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to queried default rule detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "items": [
     *      {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "msgType":"TEXT",
     *          "content":"hello,world",
     *          "status": true,
     *          "type": "SUBSCRIBE",
     *          "hitCount": 6
     *      },
     *      {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "msgType": "NEWS",
     *          "content": {
     *              "articles": [
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  },
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  }
     *              ]
     *          },
     *          "status": true,
     *          "type": "RESUBSCRIBE",
     *          "hitCount": 6
     *      }
     *  ],
     *  "_meta": {
     *      "totalCount": 2,
     *      "pageCount": 1,
     *      "currentPage": 1,
     *      "perPage": 20
     *  }
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $query = $this->getQuery();
        $channelId = $this->getChannelId();
        unset($query['channelId']);
        $defaultRules = \Yii::$app->weConnect->getDefaultRules($channelId);

        return $defaultRules;
    }

    /**
     * Update default rule
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/default-rule/init<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for update default rule
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     type: string, SUBSCRIBE, RESUBSCRIBE or DEFAULT<br/>
     *     status:string, DISABLE or ENABLE <br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles: string
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to queried default rule detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "id": "5473ffe7db7c7c2f0bee5c71",
     *  "accountId": "5473ffe7db7c7c2f0bee5c71",
     *  "replyMessage": {
     *      "msgType": "NEWS",
     *      "content": {
     *          "articles": [
     *              {
     *                  "title": "part1",
     *                  "description": "description2",
     *                  "picUrl": "http://www.baidu.com",
     *                  "sourceUrl": "http://www.baidu.com",
     *              },
     *              {
     *                  "title": "part1",
     *                  "description": "description2",
     *                  "picUrl": "http://www.baidu.com",
     *                  "sourceUrl": "http://www.baidu.com",
     *              }
     *          ]
     *      }
     *  },
     *  "type": "RESUBSCRIBE",
     *  "hitCount": 6
     * }
     * </pre>
     */
    public function actionInit()
    {
        $defaultRule = $this->getParams();
        $channelId = $this->getChannelId();
        unset($defaultRule['channelId']);
        $result = \Yii::$app->weConnect->initDefaultRule($channelId, $defaultRule);
        return $result;
    }

    /**
     * Disable default rule
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/default-rule/disable<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to disable default rule.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     type: string, SUBSCRIBE, RESUBSCRIBE or DEFAULT<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if disable fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "message": "OK"
     * }
     * </pre>
     */
    public function actionDisable()
    {
        $rule = $this->getParams();
        $accountId = $this->getChannelId();
        if (!$accountId || empty($rule['type'])) {
            throw new BadRequestHttpException('Missing channel id or type');
        }

        $rule['action'] = 'DISABLE';
        $result = \Yii::$app->weConnect->updateDefuaultRuleStatus($rule);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Disable default rule fail.');
        }
    }

    /**
     * Enable default rule
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/default-rule/enable<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to enable default rule.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     type: string, SUBSCRIBE, RESUBSCRIBE or DEFAULT<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     msg: string, if enable fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "message": "OK"
     * }
     * </pre>
     */
    public function actionEnable()
    {
        $rule = $this->getParams();
        $accountId = $this->getChannelId();
        if (!$accountId || empty($rule['type'])) {
            throw new BadRequestHttpException('Missing channel id or type');
        }

        $rule['action'] = 'ENABLE';
        $result = \Yii::$app->weConnect->updateDefuaultRuleStatus($rule);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Enable default rule fail.');
        }
    }
}
