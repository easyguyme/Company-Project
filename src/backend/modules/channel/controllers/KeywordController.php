<?php
namespace backend\modules\channel\controllers;

use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;

class KeywordController extends BaseController
{
    /**
     * Query all keywords
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keywords<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting all keywords of this channel
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     per-page: int<br/>
     *     page: int<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried keyword detail information<br/>
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
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "msgType":"TEXT",
     *          "content":"hello,world",
     *          "name": "测试关键字",
     *          "keycodes": [
     *              "wer",
     *              "code"
     *          ],
     *          "fuzzy": false,
     *          "status": "ENABLE",
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
     *          "name": "测试关键字",
     *          "keycodes": [
     *              "asd",
     *              "test"
     *          ],
     *          "fuzzy": false,
     *          "status": "ENABLE",
     *          "hitCount": 6
     *      }
     *   ],
     *  "_meta": {
     *      "totalCount": 1,
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
        $raw = \Yii::$app->weConnect->getKeywords($channelId, $query);

        if (array_key_exists('results', $raw)) {
            return [
                'items' => $raw['results'] ? $raw['results'] : [],
                '_meta' => [
                    'totalCount' => $raw['totalAmount'],
                    'pageCount' => ceil($raw['totalAmount'] / $raw['pageSize']),
                    'currentPage' => $raw['pageNum'],
                    'perPage' => $raw['pageSize']
                ]
            ];
        } else {
            throw new ServerErrorHttpException('Query keywords fail');
        }
    }

    /**
     * Create keyword message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keywords<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create keyword.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     name: string<br/>
     *     keycodes: array<br/>
     *     fuzzy: string, 0 or 1<br/>
     *     status: string DISABLE or ENABLE<br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles: array, if NEWS<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to queried keyword detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "id": "5473ffe7db7c7c2f0bee5c71",
     *      "accountId": "5473ffe7db7c7c2f0bee5c71",
     *      "replyMessage": {
     *          "msgType":"TEXT",
     *          "content":"hello,world"
     *      },
     *      "name": "测试关键字",
     *      "keycodes": [
     *          "asd",
     *          "test"
     *      ],
     *      "fuzzy": false,
     *      "status": "ENABLE",
     *      "hitCount": 0,
     *      "createTime": 1404285894043,
     *      "deleteTime": null
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $keyword = $this->getParams();
        $channelId = $this->getChannelId();

        unset($keyword['channelId']);
        $result = \Yii::$app->weConnect->createKeyword($channelId, $keyword);

        return $result;
    }

    /**
     * Remove keyword message
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keyword/{keywordId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to remove keyword.
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
    public function actionDelete($id)
    {
        $keyword = $this->getParams();
        $channelId = $this->getChannelId();
        unset($keyword['channelId']);
        if (\Yii::$app->weConnect->deleteKeyword($channelId, $id)) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Delete Keyword fail.');
        }
    }

    /**
     * Update keyword message
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keyword/{keywordId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to update keyword message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     name: string<br/>
     *     keycodes: array<br/>
     *     fuzzy: string, 0 or 1<br/>
     *     status: string, DISABLE or ENABLE<br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles: array, if NEWS<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to queried keyword detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "id": "5473ffe7db7c7c2f0bee5c71",
     *   "accountId": "5473ffe7db7c7c2f0bee5c71",
     *   "replyMessage": {
     *       "msgType":"TEXT",
     *       "content":"hello,world"
     *   },
     *   "name": "测试关键字",
     *   "keycodes": [
     *       "asd",
     *       "test"
     *   ],
     *   "fuzzy": false,
     *   "status": "ENABLE",
     *   "hitCount": 0,
     *   "createTime": 1404285894043,
     *   "deleteTime": null
     * }
     * </pre>
     */
    public function actionUpdate($id)
    {
        $keyword = $this->getParams();
        $channelId = $this->getChannelId();
        unset($keyword['channelId']);
        $keyword['id'] = $id;
        $result = \Yii::$app->weConnect->updateKeyword($channelId, $keyword);

        return $result;
    }

    /**
     * Disable keyword message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keyword/disable<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to disable keyword message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     keywordId: string<br/>
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
     *    "message": "OK"
     * }
     * </pre>
     */
    public function actionDisable()
    {
        $keyword = $this->getParams();
        $accountId = $this->getChannelId();
        if (!$accountId || empty($keyword['keywordId'])) {
            throw new BadRequestHttpException('Missing channel id or keyword id');
        }

        $keyword['action'] = 'DISABLE';
        $result = \Yii::$app->weConnect->updateKeywordStatus($keyword);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Disable keyword fail.');
        }
    }

    /**
     * Disable keyword message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keyword/enable<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to enable keyword message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     keywordId: string<br/>
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
     *    "message": "OK"
     * }
     * </pre>
     */
    public function actionEnable()
    {
        $keyword = $this->getParams();
        $accountId = $this->getChannelId();
        if (!$accountId || empty($keyword['keywordId'])) {
            throw new BadRequestHttpException('Missing channel id or keyword id');
        }
        $keyword['action'] = 'ENABLE';
        $result = \Yii::$app->weConnect->updateKeywordStatus($keyword);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Enable keyword fail.');
        }
    }

    /**
     * Query keyword message by id
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/keyword/{keywordMessageId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to query keyword message by id.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     keywordId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "id": "5473ffe7db7c7c2f0bee5c71",
     *   "accountId": "5473ffe7db7c7c2f0bee5c71",
     *   "msgType": "NEWS",
     *   "content": {
     *       "articles": [
     *           {
     *               "title": "part1",
     *               "description": "description2",
     *               "picUrl": "http://www.baidu.com",
     *               "sourceUrl": "http://www.baidu.com",
     *           },
     *           {
     *               "title": "part1",
     *               "description": "description2",
     *               "picUrl": "http://www.baidu.com",
     *               "sourceUrl": "http://www.baidu.com",
     *           }
     *       ]
     *   },
     *   "name": "测试关键字",
     *   "keycodes": [
     *       "asd",
     *       "test"
     *   ],
     *   "fuzzy": false,
     *   "status": "ENABLE",
     *   "hitCount": 6
     * }
     * </pre>
     */
    public function actionView($id)
    {
        $channelId = $this->getChannelId();
        $raw = \Yii::$app->weConnect->getKeyword($channelId, $id);
        return $raw;
    }

    public function actionTimeSeries()
    {
        $channelId = $this->getChannelId();
        $keywordId = $this->getQuery("keywordId");

        $dateCondition = [];
        $dateCondition['startDate'] = $this->getQuery("from");
        $dateCondition['endDate'] = $this->getQuery("to");

        $result = \Yii::$app->weConnect->getKeyWordsTimeSeries($channelId, $keywordId, $dateCondition);
        $destResult = $this->formateResponseData($result, ['count' => 'count'], $dateCondition['startDate'], $dateCondition['endDate']);
        return $destResult;
    }
}
