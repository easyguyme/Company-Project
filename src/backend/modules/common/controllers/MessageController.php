<?php
namespace backend\modules\common\controllers;

use backend\components\ActiveDataProvider;
use backend\models\Message;
use backend\models\Token;

class MessageController extends BaseController
{
    /**
     * Query all messages
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/message<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying all read or unread messages.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     isRead: integer, the message read status
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     _links: array, the navigation link<br/>
     *     _meta: array, pagination information<br/>
     *     items: array, json array for messages information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "isRead": 1
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    '_link': {
     *        'self': {
     *            'href': 'http:\/\/wm.com\/api\/common\/messages?tmoffset=-8&currentPage=1&isRead=0&per-page=50&page=1'
     *        }
     *    },
     *    '_meta': {
     *        'totalCount': 1,
     *        'pageCount': 1,
     *        'currentPage': 0,
     *        'perPage': 50
     *    },
     *    'items': [
     *        {
     *            'id': '557564fd2736e7e5408b4582',
     *            'title': 'title test'
     *            'accountId': '54aa37c32736e766718b4567',
     *            'content': '<a href='http://www.baidu.com'>Baidu</a>test',
     *            'isRead': false,
     *            'createdAt': '2015-06-08 17:48:45',
     *            'readAt': '2015-06-08 19:48:45',
     *            'status': 'error',
     *            'sender': {
     *                'id': '555a8cfa2736e79b1a8b4527',
     *                'from': 'system'
     *            },
     *            'to': {
     *                'id': '555a8cfa2736e79b1a8b4567',
     *                'target': 'account'
     *            }
     *        }
     *    ]
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $token = $this->getAccessToken();
        $isRead = (boolean) $this->getQuery('isRead', false);
        $tokenInfo = Token::getToken($token);

        $accountId = $tokenInfo->accountId;
        $userId = $tokenInfo->userId;

        $condition = [
            'accountId' => $accountId,
            'adminMsgId' => null,
            '$or' => [
                [
                    'to.target' => Message::TO_TARGET_ACCOUNT
                ],
                [
                    'to.target' => Message::TO_TARGET_USER,
                    'to.id' => $userId
                ]
            ],
            'isRead' => $isRead
        ];

        $query = Message::find()->where($condition)->orderBy(['createdAt' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionPortalMessage()
    {
        $accountId = $this->getAccountId();
        $condition = [
            'accountId' => $accountId,
            'adminMsgId' => [
                '$ne' => null
            ]
        ];
        return Message::findAll($condition);
    }

    /**
     * Mark all messages as read or delete all read messages
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/message/update<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for mark all messages as read or delete all read messages
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     isRead: integer, the message read status
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     status: string, update result
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "isRead": 1
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     'status': 'ok'
     * }
     * </pre>
     */
    public function actionUpdate()
    {
        $token = $this->getAccessToken();
        $isRead = (boolean) $this->getParams('isRead', false);
        $tokenInfo = Token::getToken($token);

        $accountId = $tokenInfo->accountId;
        $userId = $tokenInfo->userId;

        $condition = [
            'accountId' => $accountId,
            '$or' => [
                [
                    'to.target' => Message::TO_TARGET_ACCOUNT
                ],
                [
                    'to.target' => Message::TO_TARGET_USER,
                    'to.id' => $userId
                ]
            ],
            'isRead' => $isRead
        ];

        if ($isRead) {
            Message::deleteAll($condition);
        } else {
            Message::updateAll(['isRead' => true, 'readAt' => new \MongoDate()], $condition);
        }

        return ['status' => 'ok'];
    }

    /**
     * Mark one of the messages as read or delete the read messages
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/message/updateOne<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for mark one of the messages as read or delete the read messages
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     isRead: integer, the message read status
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     status: string, update result
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "isRead": 1
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     'status': 'ok'
     * }
     * </pre>
     */
    public function actionUpdateOne($id)
    {
        $accountId = $this->getAccountId();

        $condition = [
            '_id' => new \MongoId($id),
            'accountId' => $accountId
        ];
        Message::updateAll(['isRead' => true, 'readAt' => new \MongoDate()], $condition);

        return ['status' => 'ok'];
    }
}
