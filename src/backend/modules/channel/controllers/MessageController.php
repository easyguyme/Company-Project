<?php
namespace backend\modules\channel\controllers;

use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;

class MessageController extends BaseController
{

    /**
     * Query interact message lists
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/messages<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting all interact messages.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     time: 0: all; 1: today; 2: yesterday, 3: the day before yesterday; 4: long ago<br/>
     *     ignoreKeywordHit: 0: not to ignore event message; 1 ignore event message
     *     per-page: int<br/>
     *     page: int<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried messages detail information<br/>
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
     *          "direction": "SEND",
     *          "message": {
     *              "fromUser": "oK5i0t3WECQx4wvCfDPtAzNP-mDc",
     *              "toUser": "gh_68ac2bff67ba",
     *              "msgType": "TEXT",
     *              "content": "abc",
     *              "createTime": "2014-12-02 08:17:01"
     *          },
     *          "sender": {
     *              "id": "5473ffe7db7c7c2f0bee5c71",
     *              "accountId": "5473ffe7db7c7c2f0bee5c71",
     *              "originId": "oYoUVt2zT6mtrFn9T0nlNWkzJbEo",
     *              "nickname": "hank",
     *              "gender": "FEMALE",
     *              "city": "武汉",
     *              "province": "湖北",
     *              "country": "中国",
     *              "headerImgUrl": "http://ssp-stage.qiniudn.com/avatar/oib7yt7612cCWuzWj1J5r9kl8-VU.jpg",
     *              "subscribeTime": 1416871637000,
     *              "unionId": "12.0",
     *              "massSendUsageCount": 4,
     *              "tags": [
     *                  "高富帅",
     *                  "白富美"
     *              ],
     *              "subscribeSource": "other",
     *              "firstSubscribeSource": "other",
     *              "interactMessageCount": 1,
     *              "lastInteractMessageTime": null,
     *              "lastInteractEventTime": 1417217237000
     *          },
     *      },
     *      {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "direction": "SEND",
     *          "hitKeycode": "hello",
     *          "message": {
     *              "fromUser": "oK5i0t3WECQx4wvCfDPtAzNP-mDc",
     *              "toUser": "gh_68ac2bff67ba",
     *              "msgType": "TEXT",
     *              "content": "hello world",
     *              "createTime": "2014-12-02 08:17:01"
     *          },
     *          "sender": {
     *              "id": "5473ffe7db7c7c2f0bee5c71",
     *              "accountId": "5473ffe7db7c7c2f0bee5c71",
     *              "originId": "oYoUVt2zT6mtrFn9T0nlNWkzJbEo",
     *              "nickname": "hank",
     *              "gender": "FEMALE",
     *              "city": "武汉",
     *              "province": "湖北",
     *              "country": "中国",
     *              "headerImgUrl": "http://ssp-stage.qiniudn.com/avatar/oib7yt7612cCWuzWj1J5r9kl8-VU.jpg",
     *              "subscribeTime": 1416871637000,
     *              "unionId": "12.0",
     *              "massSendUsageCount": 4,
     *              "tags": [
     *                  "高富帅",
     *                  "白富美"
     *              ],
     *              "subscribeSource": "other",
     *              "firstSubscribeSource": "other",
     *              "interactMessageCount": 1,
     *              "lastInteractMessageTime": null,
     *              "lastInteractEventTime": 1417217237000
     *          },
     *      },
     *      }
     *  ]
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
        if (!isset($query['time'])) {
            $query['time'] = 0;
        }

        $today = strtotime(date('Y-m-d')) * 1000;
        $yesterday = strtotime(date('Y-m-d', strtotime('-1 day'))) * 1000;
        $bfYesterday = strtotime(date('Y-m-d', strtotime('-2 day'))) * 1000;

        switch ($query['time']) {
            case 0:
                break;
            case 1:
                $query['startTime'] = $today;
                break;
            case 2:
                $query['startTime'] = $yesterday;
                $query['endTime'] = $today;
                break;
            case 3:
                $query['startTime'] = $bfYesterday;
                $query['endTime'] = $yesterday;
                break;
            case 4:
                $query['endTime'] = $bfYesterday;
                break;
            default:
                break;
        }

        unset($query['time']);
        if (isset($query['searchKey'])) {
            $query['matchContent'] = $query['searchKey'];
            unset($query['searchKey']);
        }

        $raw = \Yii::$app->weConnect->getAccountMessages($channelId, $query);

        if (array_key_exists('results', $raw)) {
            // $count = count($raw['results']);
            // for ($i =0; $i < $count; $i++) {// filter just TEXT message now
            //     if ($raw['results'][$i]['message']['msgType'] != 'TEXT') {
            //         unset($raw['results'][$i]['message']);
            //     }
            // }
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
            throw new ServerErrorHttpException('Query message fail.');
        }
    }

    /**
     * Create interact message
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/messages<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for create interact messages.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     fromUser: string, channel account id like "gh_68ac2bff67ba"<br/>
     *     toUser: string, the user's origin id<br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles, if NEWS<br/>
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
        $toUserId = $this->getParams('toUser');
        $channelId = $this->getChannelId();

        $message = [
            'msgType' => $this->getParams('msgType'),
            'createTime' => TimeUtil::msTime()
        ];

        switch ($message['msgType']) {
            case 'TEXT':
                $message['content'] = $this->getParams('content');
                break;

            case 'NEWS':
                $content = $this->getParams('content');
                $message['content'] = $content;
                break;

            default:
                # code...
                break;
        }

        $result = \Yii::$app->weConnect->sendCustomerServiceMessage($toUserId, $channelId, $message);
        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException(\Yii::t('channel', 'message_error'));
        }
    }

    /**
     * Get interact message
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/message/interact-message?channelId={channelId}&userId={userId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for get interact message.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     userId: string<br/>
     *     per-page: int<br/>
     *     next: sting<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  {
     *   "pageSize": 20,
     *   "pageNum": 1,
     *   "totalAmount": 37,
     *   "next": "1434611703383",
     *   "results": [
     *       {
     *           "id": "557542dee4b0575bc90a8534",
     *           "accountId": "54d9c155e4b0abe717853ee1",
     *           "userId": "54dd98f3ad274f0b473eefaf",
     *           "msgType": "TEXT",
     *           "direction": "SEND",
     *           "keycode": "RESUBSCRIBE",
     *           "matchedRuleId": "54f54bf3e4b0c5896e262763",
     *           "createTime": 1433748190340,
     *           "message": {
     *               "fromUser": "gh_b7f586690646",
     *               "toUser": "oC9Aes4MC9mwhsZsxElT2VB-tzY4",
     *               "msgType": "TEXT",
     *               "content": "Hi,欢迎再次回到熊猫Baby微信平台!e",
     *               "createTime": 1433748190340
     *           }
     *       },
     *       {
     *           "id": "55753a6de4b0575bc90a8526",
     *           "accountId": "54d9c155e4b0abe717853ee1",
     *           "userId": "54dd98f3ad274f0b473eefaf",
     *           "msgType": "NEWS",
     *           "direction": "SEND",
     *           "createTime": 1433746028950,
     *           "message": {
     *               "fromUser": "gh_b7f586690646",
     *               "toUser": "oC9Aes4MC9mwhsZsxElT2VB-tzY4",
     *               "msgType": "NEWS",
     *               "articles": [
     *                   {
     *                       "title": "aaaaaaaaaaaaaa",
     *                       "url": "http://vincenthou.qiniudn.com/efdbca4798c74ccd1f4658f0.jpg",
     *                       "content": "<p>aaaaa</p>",
     *                       "contentUrl": "http://vincenthou.qiniudn.com/1fac5289-3c14-8754-45b0-0cef902d214f.html"
     *                   },
     *                   {
     *                       "title": "aaaa",
     *                       "url": "http://vincenthou.qiniudn.com/4ba85b74e80c5a0b6bde8a39.jpeg",
     *                       "content": "<p>aaa</p>",
     *                       "contentUrl": "http://vincenthou.qiniudn.com/7792e687-9d40-1c8a-cace-d3a4b101ed14.html"
     *                   },
     *                   {
     *                       "title": "aaaaa",
     *                       "description": "",
     *                       "url": "http://vincenthou.qiniudn.com/4cace7b65b56b631b90886d4.jpg",
     *                       "content": "<p>aaaaa</p>",
     *                       "contentUrl": "http://vincenthou.qiniudn.com/bd4406f0-4201-5bda-1bb7-410da220b8a2.html"
     *                   }
     *               ],
     *               "createTime": 1433746028950
     *           }
     *       }
     *      ]
     *  }
     * </pre>
     */
    public function actionInteractMessage()
    {
        $perPage = $this->getQuery('per-page');
        $next = $this->getQuery('next');
        $userId = $this->getQuery('userId');
        $channelId = $this->getChannelId();

        if (empty($channelId)) {
            throw new BadRequestHttpException(Yii::t('channel', 'invalid_member_id'));
        }

        if (empty($userId)) {
            throw new BadRequestHttpException(Yii::t('channel', 'invalid_user_id'));
        }

        $condition = ['pageSize' => $perPage, 'next' => $next];
        $result = \Yii::$app->weConnect->getInteractMessages($channelId, $userId, $condition);
        return $result;
    }
}
