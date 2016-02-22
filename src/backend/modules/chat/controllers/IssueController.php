<?php

namespace backend\modules\chat\controllers;

use Yii;
use yii\base\Component;
use backend\components\ActiveDataProvider;
use backend\modules\helpdesk\models\Issue;
use backend\modules\helpdesk\models\IssueUser;
use backend\modules\helpdesk\models\IssueActivity;
use backend\modules\helpdesk\models\IssueAttachment;
use backend\exceptions\InvalidParameterException;
use yii\helpers\ArrayHelper;
use yii\base\Exception;
use backend\utils\LogUtil;
use backend\utils\UrlUtil;

/**
 * Controller class for help desk issues.
 **/
class IssueController extends RestController
{
    public $modelClass = "backend\modules\helpdesk\models\Issue";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['view'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();

        $whereCondition = [
            'accountId' => $accountId,
            'isDeleted' => Issue::NOT_DELETED,
        ];

        if (!empty($params['status'])) {
            $whereCondition['status'] = $params['status'];
        }

        $totalCount = Issue::count($whereCondition);
        $currentPage = $params['page'];
        $perPage = $params['per-page'];
        $offset = ($currentPage - 1) * $perPage;
        $pageCount = (int)(($totalCount - 1) / $perPage + 1);

        $issues = Issue::search($whereCondition, $offset, $perPage);

        foreach ($issues as $issue) {
            $issue->creator = $issue->creatorDetail;
        }

        return [
            'totalCount' => $totalCount,
            'currentPage' => $currentPage,
            'perPage' => $perPage,
            'pageCount' => $pageCount,
            'issues' => $issues,
        ];
    }

    /**
     * Creates help desk issue
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/issues<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to create issue and issueActivity.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: string, the access token<br/>
     *     title: string, the title of issue<br/>
     *     description: string, the description of issue<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     return saved issue detail, if success, ortherwise, return all error messages.<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "accesstoken" : "eeaa31a8-1d55-247e-70a2-bc9af23918ec",
     *     "title" : "This is a test issue.",
     *     "description" : "This is a test issue's description."
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "id": "55c72d5e0faf308f048b4567",
     *    "title": "测试Function CreateActivity--2",
     *    "description": "如果成功返回结果ok",
     *    "status": "open",
     *    "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *    },
     *    "assignee": null,
     *    "createdAt": 1439116638254,
     *    "attachments": []
     * }
     * </pre>
     */
    public function actionCreate()
    {
        return $this->_createIssue();
    }

    // This action would not check auth and only for creating issue from feedback js sdk, other conditions, go to action 'create'.
    // Note that you need to put parameter "accountId" in your request body.
    public function actionCreateFromJsSdk()
    {
        return $this->_createIssue();
    }

    private function _createIssue()
    {
        $params = $this->getParams();
        $issue = new Issue();
        $issue->attributes = $params;
        $issue->origin = $params['origin'];
        $issue->status = Issue::STATUS_OPEN;

        $accountId = '';
        if (isset($params['accountId'])) {
            $accountId = new \MongoId($params['accountId']);
        } else {
            $accountId = $this->getAccountId();
        }
        $issue->accountId = $accountId;

        if ($params['origin'] !== IssueUser::HELPDESK) {
            $issue->creator = $this->_createIssueUser($params);
        } else {
            $issue->creator = $this->_getCurrentUser();
        }

        if (isset($params['attachments'])) {
            $attachments = $params['attachments'];

            if (IssueAttachment::batchInsert($attachments)) {
                $issue->attachmentIds = $this->_getIdArray($attachments);
            }
        }

        if ($issue->save()) {
            $this->_createActivity($issue);
            $issue->creator = $issue->creatorDetail;
            $data = $issue->toArray();
            $this->_sendTuisongbaoEvent($params, Issue::EVENT_NEW_ISSUE, $data, $accountId);
            return $data;
        }

        return ['errors' => $issue->errors];
    }

    private function _createIssueUser($params)
    {
        $issueUser = new IssueUser();
        $issueUser->origin = $params['origin'];
        $issueUser->attributes = $params;

        switch ($params['origin']) {
            case IssueUser::WEIBO:
            case IssueUser::ALIPAY:
            case IssueUser::WECHAT:
                $follower = Yii::$app->weConnect->getFollowerByOriginId($params['openId'], $params['channelId']);
                if (empty($follower)) {
                    throw new InvalidParameterException(['issue'=>Yii::t('common', 'parameters_missing')]);
                }
                $issueUser->avatar = $follower['headerImgUrl'];
                $issueUser->openId = $follower['originId'];
                $issueUser->nickname = $follower['nickname'];
                $issueUser->gender = $follower['gender'];
                $issueUser->channelId = $follower['accountId'];
                $issueUser->language = $follower['language'];
                $issueUser->location = [
                    'city' => $follower['city'],
                    'province' => $follower['province'],
                    'country' => $follower['country'],
                ];
                break;
        }

        if ($issueUser->save()) {
            return $issueUser['_id'];
        }
        return ['errors' => $issueUser->errors];
    }

    private function _getCurrentUser()
    {
        $currentUser = $this->getUser();
        return $currentUser['_id'];
    }

    /**
     * Updates help desk issue's status.
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/issue/{id}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to update issue and issueActivity.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: string, the access token<br/>
     *     status: string, status of issue to update<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     return updated issue detail and previous status before issue was updated, if success,
     *     ortherwise, throw an invalid parameters exception.<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "accesstoken" : "eeaa31a8-1d55-247e-70a2-bc9af23918ec",
     *     "id": "55c446c60faf303f0b8b456a",
     *     "status" : "assigned"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  * {
     *    "id": "55c446c60faf303f0b8b456a",
     *    "title": "测试Function CreateActivity--2",
     *    "description": "如果成功返回结果ok",
     *    "status": "assigned",
     *    "creator": {
     *         "id": "55c446c60faf303f0d8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *    },
     *    "assignee": {
     *        "id": "55c446c60faf303f0d8b456a",
     *         "name": "elvisma",
     *         "email": "elvisma@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *    },
     *    "createdAt": 1439116638254,
     *    "isDeleted": false,
     *    "attachments": [],
     *    "priviousStatus": "open"
     * }
     * </pre>
     */
    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        $id = new \MongoId($id);

        $issue = Issue::findByPk($id);

        if (empty($issue)) {
            throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_id_does_not_exist')]);
        } else {
            $previousStatus = $issue['status'];
            $statusToChange = $params['status'];

            $condition = [];
            $attributes = [];

            $condition['_id'] = $id;
            $attributes['status'] = $statusToChange;

            switch ($statusToChange) {
                case Issue::STATUS_ASSIGNED:
                    $condition['status'] = Issue::STATUS_OPEN;
                    $attributes['assignee'] = $this->_getCurrentUser();
                    break;
                case Issue::STATUS_RESOLVED:
                    $condition['status'] = Issue::STATUS_ASSIGNED;
                    break;
                case Issue::STATUS_CLOSED:
                    $condition['status'] = [Issue::STATUS_OPEN, Issue::STATUS_ASSIGNED, Issue::STATUS_RESOLVED];
                    break;
                default:
                    break;
            }

            $updatedCount = Issue::updateAll($attributes, $condition);
            if ($updatedCount === 1) {
                if ($statusToChange === Issue::STATUS_RESOLVED) {
                    $this->_sendIssueResolvedMail($issue, $accountId);
                }
                if ($statusToChange === Issue::STATUS_ASSIGNED) {
                    $issue['assignee'] = $attributes['assignee'];
                }
                $issue['status'] = $statusToChange;
                $issueActivity = $this->_createActivity($issue);
                $issue->creator = $issue->creatorDetail;
                $data = array_merge($issue->toArray(), ['previousStatus' => $previousStatus, 'newActivity' => $issueActivity]);
                $this->_sendTuisongbaoEvent($params, Issue::EVENT_ISSUE_STATUS_CHANGED, $data, $accountId);

                return $data;
            } else {
                switch ($previousStatus) {
                    case Issue::STATUS_OPEN:
                        throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_should_be_assigned_first')]);
                        break;
                    case Issue::STATUS_ASSIGNED:
                        throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_has_already_been_claimed')]);
                        break;
                    case Issue::STATUS_RESOLVED:
                        throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_has_already_been_resolved')]);
                        break;
                    case Issue::STATUS_CLOSED:
                        throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_has_already_been_closed')]);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function _createActivity($issue, $description = null)
    {
        $issueActivity = new IssueActivity();
        $issueActivity->issueId = $issue['_id'];
        $issueActivity->description = $description;
        if ($issue->status === Issue::STATUS_OPEN) {
            $issueActivity->creator = $issue->creator;
        } else {
            $issueActivity->creator = $this->_getCurrentUser();
        }

        switch ($issue->status) {
            case Issue::STATUS_OPEN:
                $issueActivity->action = IssueActivity::ACTION_CREATE;
                break;
            case Issue::STATUS_ASSIGNED:
                $issueActivity->action = IssueActivity::ACTION_CLAIM;
                break;
            case Issue::STATUS_RESOLVED:
                $issueActivity->action = IssueActivity::ACTION_RESOLVE;
                break;
            case Issue::STATUS_CLOSED:
                $issueActivity->action = IssueActivity::ACTION_CLOSE;
                break;
            default:
                $issueActivity->action = IssueActivity::ACTION_COMMENT;
                break;
        }
        $issueActivity->save();
        $issueActivity->creator = $issueActivity->creatorDetail;

        return $issueActivity;
    }

    private function _sendIssueResolvedMail($issue, $accountId)
    {
        $host = UrlUtil::getDomain();
        $creator = $issue->creatorDetail;
        $email = mb_strtolower($creator['email']);
        $vars = [
            'name' => $creator['name'],
            'title' => $issue['title'],
            'link' => $host . '/chat/issue/' . $issue['_id'],
        ];
        $mail = Yii::$app->mail;
        $mail->setView('//mail/issueResolved', $vars, '//layouts/email');
        $mail->sendMail($email, '群脉工单解决通知', $accountId);

        return ['status' => 'ok'];
    }

    /**
     * Views help desk issue and its activities.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/issue/{id}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to view issue and corresponding activities.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: string, the access token<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     return issue detail and its all related activities and attachements, if success,
     *     ortherwise, throw an invalid parameters exception.<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "accesstoken" : "eeaa31a8-1d55-247e-70a2-bc9af23918ec",
     *     "id": "55c446c60faf303f0b8b456a"
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "issue": {
     *    "id": "55c72d5e0faf308f048b4567",
     *    "title": "测试Function CreateActivity--2",
     *    "description": "如果成功返回结果ok",
     *    "status": "resolved",
     *    "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *    },
     *    "assignee": null,
     *    "createdAt": 1439116638125,
     *    "attachments": [
     *    {
     *        "id": "55cc90d70faf30e21a8b4573",
     *        "name": "195750372",
     *        "type": "image/jpeg",
     *        "size": "0.01",
     *        "url": "http://vincenthou.qiniudn.com/4f8dd288bc5291273c91d43d.jpg",
     *        ...
     *    }],
     *    "activities": [
     *    {
     *      "id": "55c72d5e0faf308f048b4568",
     *      "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *      },
     *      "action": "create",
     *      "description": "测试Function CreateActivity",
     *      "createdAt": 1439116638254
     *    },
     *    {
     *      "id": "55c72e2f0faf3090048b4568",
     *      "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *      },
     *      "action": "claim",
     *      "description": "",
     *      "createdAt": 1439116638254,
     *    }
     *  ]
     *}
     * </pre>
     */
    public function actionView($id)
    {
        $issue = Issue::findByPk($id);
        if (empty($issue)) {
            throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_id_does_not_exist')]);
        }

        $issue->creator = $issue->creatorDetail;
        $issue->assignee = $issue->assigneeDetail;

        $activities = $issue->activities;

        foreach ($activities as $activity) {
            $activity->creator = $activity->creatorDetail;
        }

        $result = array_merge(ArrayHelper::toarray($issue), [
            'attachments' => $issue->attachments,
            'activities' => $issue->activities,
            ]);

        return $result;
    }

    /**
     * Deletes a help desk issue.
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/issue/{id}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to delete an issue.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: string, the access token<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     return deleted issue data and previous status before issue was deleted, if success,
     *     ortherwise, throw an invalid parameters exception.<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "accesstoken" : "eeaa31a8-1d55-247e-70a2-bc9af23918ec",
     *     "id": "55c446c60faf303f0b8b456a",
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "id": "55c72d5e0faf308f048b4567",
     *    "title": "测试Function CreateActivity--2",
     *    "description": "如果成功返回结果ok",
     *    "status": "open",
     *    "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *    },
     *    "assignee": null,
     *    "createdAt": 1439116638254,
     *    "isDeleted": true,
     *    "attachments": [],
     *    "priviousStatus": "open"
     * }
     * </pre>
     */
    public function actionDelete($id)
    {
        $params = $this->getParams();

        $issue = Issue::findByPk($id);
        if (empty($issue)) {
            throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_id_does_not_exist')]);
        }

        $previousStatus = $issue->status;
        $issue['isDeleted'] = true;
        $issue->save();

        $data = array_merge($issue->toArray(), ['previousStatus' => $previousStatus]);
        $this->_sendTuisongbaoEvent($params, Issue::EVENT_ISSUE_STATUS_CHANGED, $data, $issue->accountId);

        return $data;
    }

    /**
     * Comments a help desk issue.
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/chat/issue/comment/{id}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for help desk to comment an issue.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accesstoken: string, the access token<br/>
     *     description: string, the issue's comment<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     return new comment activity, if success, ortherwise, throw an invalid parameters exception.<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <pre>
     * {
     *     "accesstoken" : "eeaa31a8-1d55-247e-70a2-bc9af23918ec",
     *     "id": "55c446c60faf303f0b8b456a",
     *     "description": "This is a comment from help desk."
     * }
     * </pre>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "id": "55c446c60faf303f0b8b456a",
     *     "description": "This is a comment from help desk.",
     *     "action": comment,
     *     "creator": {
     *         "id": "55c446c60faf303f0b8b456a",
     *         "name": "byronzhang",
     *         "email": "byronzhang@quncrm.com"
     *         "avatar": "http://vincenthou.qiniudn.com/52a82080e78c991bc0ca3d3a.png"
     *         ...
     *     },
     *     "createdAt": 1439116638254,
     *    "issueId": "55c446c60faf303f0b8b4fe3"
     *
     * }
     * </pre>
     */
    public function actionComment($id)
    {
        $issue = Issue::findByPk($id);
        if (empty($issue)) {
            throw new InvalidParameterException(['issue'=>Yii::t('issue', 'issue_id_does_not_exist')]);
        } else {
            $params = $this->getParams();

            if (!isset($params['description']) || empty($params['description'])) {
                throw new InvalidParameterException(['issue'=>Yii::t('issue', 'comment_can_not_be_empty')]);
            } else {
                $description = $params['description'];
                unset($issue->status);
                $issueActivity = $this->_createActivity($issue, $description);

                $issueActivity->creator = $issueActivity->creatorDetail;
                $data = $issueActivity->toArray();
                $this->_sendTuisongbaoEvent($params, Issue::EVENT_COMMENT_ISSUE, $data, $issue->accountId);

                return $data;
            }
        }
    }

    public function actionRemoveAttachment()
    {
        $params = $this->getParams();
        $key = $params['qiniu'];
        Yii::$app->qiniu->deleteFile($key);

        return ['message' => 'ok'];
    }

    private function _sendTuisongbaoEvent($params, $event, $data, $accountId)
    {
        try {
            if (isset($params['socketId']) && !empty($params['socketId'])) {
                $socketId = $params['socketId'];
                Yii::$app->tuisongbao->triggerEvent($event, $data, [Issue::CHANNEL_ISSUE_PREFIX . $accountId], $socketId);
                return;
            }
            if (isset($params['origin']) && $params['origin'] !== IssueUser::HELPDESK) {
                Yii::$app->tuisongbao->triggerEvent($event, $data, [Issue::CHANNEL_ISSUE_PREFIX . $accountId]);
            }
        } catch (Exception $e) {
            LogUtil::error(['class' => get_called_class(), 'exception' => $e->getMessage()]);
        }
    }

    private function _getIdArray($models)
    {
        $idArray = [];
        foreach ($models as $model) {
            $idArray[] = $model['_id'] . '';
        }

        return $idArray;
    }
}
