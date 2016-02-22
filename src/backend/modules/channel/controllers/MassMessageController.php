<?php
namespace backend\modules\channel\controllers;

use Yii;
use backend\exceptions\InvalidParameterException;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;

class MassMessageController extends BaseController
{
    /**
     * Temp qrcode expire time(seconds)
     */
    const TEMP_QRCODE_EXPIRE = 1800;

    /**
     * Query Mass message lists
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chennel/mass-messages<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting all mass messages.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string,<br/>
     *     status: string, FINISHED or SCHEDULED<br/>
     *     per-page:<br/>
     *     page:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried mass-messages detail information<br/>
     *     _meta: object, page information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "items": [
     *      {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "msgType": "MPNEWS",
     *          "content": {
     *              "articles": [
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "author": "david",
     *                      "content": "阿森纳打算打",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  },
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "author": "david",
     *                      "content": "阿森纳打算打",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  }
     *              ]
     *          },
     *          "userQuery": {
     *              "tags": ["asd", "asxzc"], //if send by tags
     *              "gender": "MALE",
     *              "country": "中国",
     *              "province": "上海",
     *              "city": "浦东新区"
     *          },
     *          "scheduleTime": 1208946008000,
     *          "status": "FINISHED",
     *          "messageId": [1,2],
     *          "resultNotificationCount": 4,
     *          "totalCount": 4,
     *          "filterCount": 0,
     *          "sentCount": 2,
     *          "errorCount": 2
     *      }
     *  ],
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
        if (isset($query['status']) && $query['status'] == 'FINISHED') {
            $query['status'] = 'SUBMITED,FINISHED,FAILED';
        }

        $channelId = $this->getChannelId();
        $raw = Yii::$app->weConnect->getMassMessages($channelId, $query);

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
            throw new ServerErrorHttpException('Query mass messages fail.');
        }
    }

    /**
     * Send Mass message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/mass-messages<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for sending mass message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     scheduleTime: long, the scheduled time, or empty which means to send right now<br/>
     *     userQuery.tags: array<br/>
     *     userQuery.gender: male, female or empty<br/>
     *     userQuery.country: string<br/>
     *     userQuery.province: string<br/>
     *     userQuery.city: string<br/>
     *     msgType: TEXT or MPNEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles, if MPNEWS<br/>
     *     mixed: bool<br/>
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
    public function actionCreate()
    {
        $massmessage = $this->getParams();
        $channelId = $this->getChannelId();

        if (!empty($massmessage['scheduleTime']) && $massmessage['scheduleTime'] < TimeUtil::msTime()) {
            throw new InvalidParameterException(['schedule-picker' => Yii::t('channel', 'schedule_time_error')]);
        }

        unset($massmessage['channelId']);
        $result = Yii::$app->weConnect->createMassMessage($channelId, $massmessage);
        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException(\Yii::t('channel', 'message_error'));
        }
    }

    /**
     * Send to preview mass message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/mass-message/preview<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for sending to preview mass message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     msgType: TEXT or MPNEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles, if MPNEWS<br/>
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
     *   "qrcode": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQG/7joAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xLzFFZ2xZcG5taVpudzh2U0wzbWJQAAIEekd1VQMECAcAAA==",
     *   "expireTime": 1800
     * }
     * </pre>
     */
    public function actionPreview()
    {
        $qrcode = $this->getParams();
        $channelId = $this->getChannelId();
        $qrcode['name'] = StringUtil::uuid();
        $qrcode['type'] = 'PREVIEW';
        $qrcode['temporary'] = true;
        $qrcode['expireSeconds'] = static::TEMP_QRCODE_EXPIRE;
        unset($qrcode['channelId']);
        $result = Yii::$app->weConnect->createQrcode($channelId, $qrcode);

        if ($result && isset($result['imageUrl'])) {
            return ['qrcode' => $result['imageUrl'], 'expireTime' => static::TEMP_QRCODE_EXPIRE];
        } else {
            throw new ServerErrorHttpException('Create mass message fail.');
        }
    }

    /**
     * Delete Mass message
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/mass-message/{massMessageId}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for delete mass message.
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
        $param = $this->getParams();
        $channelId = $this->getChannelId();
        unset($param['channelId']);
        if (Yii::$app->weConnect->deleteMassMessage($channelId, $id)) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Delete mass message fail.');
        }
    }

    /**
     * Update Mass message
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/mass-message/{massMessageId}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for update mass message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     scheduleTime: long, the scheduled time, or empty which means to send right now<br/>
     *     userQuery.tags: array<br/>
     *     userQuery.gender: MALE, FEMALE or empty<br/>
     *     userQuery.country: string<br/>
     *     userQuery.province: string<br/>
     *     userQuery.city: string<br/>
     *     msgType: TEXT or MPNEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles, if MPNEWS<br/>
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
    public function actionUpdate($id)
    {
        $massMessage = $this->getParams();
        $channelId = $this->getChannelId();
        unset($massMessage['channelId']);

        $massMessage['id'] = $id;
        $result = Yii::$app->weConnect->updateMassMessage($channelId, $massMessage);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Update mass message fail.');
        }
    }

    /**
     * Query mass-message by id
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/mass-message/{massMessageId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for query mass message by id.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     messageId: string<br/>
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
     *   "userQuery": {
     *       "country": "中国",
     *       "province": "上海",
     *       "city": "浦东新区"
     *   },
     *  "msgType": "MPNEWS",
     *  "content": {
     *       "articles": [
     *           {
     *               "title": "新闻",
     *               "description": "APEC会议举行第三天",
     *               "url": "http://www.baidu.com/image.jpg",
     *               "author": "david",
     *               "content": "hello",
     *               "sourceUrl": "http://www.baidu.com",
     *               "showCoverPic": true
     *           },
     *           {
     *               "title": "新闻",
     *               "description": "APEC会议举行第三天",
     *               "url": "http://www.baidu.com/image.jpg",
     *               "author": "david",
     *               "content": "hello",
     *               "sourceUrl": "http://www.baidu.com",
     *               "showCoverPic": true
     *           }
     *       ]
     *   },
     *   "scheduleTime": 1208946008000,
     *   "status": "FINISHED",
     *   "totalCount": 4,
     *   "filterCount": 0,
     *   "sentCount": 2,
     *   "errorCount": 2,
     *   "createTime": 1404285894043,
     *   "finishTime": 1404285894043
     * }
     * </pre>
     */
    public function actionView($id)
    {
        $channelId = $this->getChannelId();
        $raw = Yii::$app->weConnect->getMassMessage($channelId, $id);
        return $raw;
    }
}
