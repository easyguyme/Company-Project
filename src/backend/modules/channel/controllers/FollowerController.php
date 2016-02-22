<?php
namespace backend\modules\channel\controllers;

use Yii;
use backend\components\WeConnect;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use yii\helpers\ArrayHelper;
use backend\models\Follower;

class FollowerController extends BaseController
{

    /**
     * Query user list
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/followers<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying users by propertities.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     tags: array, if more than one tag, implode by comma<br/>
     *     gender: MALE, FEMALE<br/>
     *     country: string<br/>
     *     province: string<br/>
     *     city: string<br/>
     *     subscribeTimeFrom: timestamp<br/>
     *     nickname: string<br/>
     *     per-page: int<br/>
     *     page: int<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried channels detail information<br/>
     *     _meta: array, the pagination information.
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "items": [
     *      {
     *          "id": "548028238d7a9e9dc8397f8a",
     *          "accountId": "548024cfe4b0cea2642d3c78",
     *          "subscribed": true,
     *          "originId": "oYoUVt2zT6mtrFn9T0nlNWkzJbEo",
     *          "nickname": "hank",
     *          "gender": "Female",
     *          "language": "english",
     *          "city": "武汉",
     *          "province": "湖北",
     *          "country": "中国",
     *          "headerImgUrl": "http://ssp-stage.qiniudn.com/avatar/oib7yt7612cCWuzWj1J5r9kl8-VU.jpg",
     *          "subscribeTime": "2014-12-02 08:17:01",
     *          "unionId": "12.0",
     *          "massSendUsageCount": 4,
     *          "tags": [
     *              "高富帅",
     *              "白富美"
     *          ],
     *          "subscribeSource": "other",
     *          "firstSubscribeSource": "other",
     *          "firstSubscribeTime": "2014-12-02 08:17:01",
     *          "interactMessageCount": 1,
     *          "lastInteractMessageTime": null,
     *          "lastInteractEventTime": "2014-12-02 08:17:01",
     *          "createTime": "2014-12-02 08:17:01",
     *          "unsubscribeTime": null
     *      },
     *      {
     *          "id": "54803daf8d7a9e9dc8397f8b",
     *          "accountId": "548024cfe4b0cea2642d3c78",
     *          "subscribed": true,
     *          "originId": "oYoUVt2zT6mtrFn9T0nlNWkzJbEo",
     *          "nickname": "clark",
     *          "gender": "Female",
     *          "language": "english",
     *          "city": "青岛",
     *          "province": "山东",
     *          "country": "中国",
     *          "headerImgUrl": "http://ssp-stage.qiniudn.com/avatar/oib7yt7612cCWuzWj1J5r9kl8-VU.jpg",
     *          "subscribeTime": "2014-12-02 08:17:01",
     *          "unionId": "12.0",
     *          "massSendUsageCount": 4,
     *          "tags": [
     *              "高富帅",
     *              "白富美"
     *          ],
     *          "subscribeSource": "other",
     *          "firstSubscribeSource": "other",
     *          "firstSubscribeTime": "2014-12-02 08:17:01",
     *          "interactMessageCount": 1,
     *          "lastInteractMessageTime": null,
     *          "lastInteractEventTime": "2014-12-02 08:17:01",
     *          "createTime": "2014-12-02 08:17:01",
     *          "unsubscribeTime": null
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
        $accountId = $this->getAccountId();

        if (empty($query['channelId'])) {
            throw new BadRequestHttpException("Missing channel id");
        }

        $channelId = $query['channelId'];
        unset($query['channelId']);
        $query['pageSize'] = $query['per-page'];
        $query['pageNum'] = $query['page'];
        unset($query['per-page']);
        unset($query['page']);
        $raw = Yii::$app->weConnect->getFollowers($channelId, $query);

        if (array_key_exists('results', $raw)) {
            return [
                'items' => $raw['results'],
                '_meta' => [
                    'totalCount' => $raw['totalAmount'],
                    'pageCount' => ceil($raw['totalAmount'] / $raw['pageSize']),
                    'currentPage' => $raw['pageNum'],
                    'perPage' => $raw['pageSize']
                ]
            ];
        } else {
            throw new ServerErrorHttpException(Yii::t('channel', 'api_data_exception'));
        }
    }

    /**
     * Get map openId => name
     * @param string $members
     * @return array
     */
    private function getOpenIdNameMap($members)
    {
        $map = [];
        foreach ($members as $member) {
            $name = $member->getDefaultProperty(Member::DEFAULT_PROPERTIES_NAME);
            $map[$member->openId] = $name;
            $socials = empty($member->socials) ? [] : $member->socials;
            foreach ($socials as $social) {
                $map[$social['openId']] = $name;
            }
        }

        return $map;
    }

    /**
     * Query a follower by id
     *
     * <b>Request Type: </b> GET<br/>
     * <b>Request Endpoint: </b> http://{server-domain}/api/channel/follower/{id}?channelId={channelId}
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary </b>: This api is for query the detail informatiion of a follower.
     *
     * <b>Request Parameters: </b><br/>
     *      id: the id of the follower<br/>
     *      channelId: the account id of the wechat account<br/>
     *
     * <b>Response Parameters: </b><br/>
     *      reference to: http://git.augmentum.com.cn/scrm/we-connect/blob/develop/docs/api.md#get-one-user-by-id.<br/>
     *
     * <b>Response Example: </b>
     *      //TODO
     **/
    public function actionView($id)
    {
        $channelId = $this->getQuery('channelId');
        if (empty($channelId)) {
            throw new BadRequestHttpException("Error Processing Request", 1);
        }

        return Yii::$app->weConnect->getFollower($id, $channelId);
    }

    /**
     * Add tags to users
     *
     * <b>Request Method: </b>POST<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/channel/follower/add-tags<br/>
     * <b>Content-type: </b>application/json<br/>
     * <b>Summary: </b>This api is for add tags to followers<br/>
     *
     * <b>Request Parameters: </b><br/>
     *  followers: array[string], the ids of the followers.<br/>
     *  tags: array[string], the tags.
     * <b>Request Example: </b><br/>
     * {followers:["abasfdasfasdf", "fasfsavasgfw"], tags:["VIP", "new", "这些是XX"], channelId:""}
     *
     * <b>Response Example: </b><br/>
     * <pre>
     *  {"result":"success"}
     *   OR {"result":"fail"}
     * </pre>
     **/
    public function actionAddTags()
    {
        $channelId = $this->getChannelId();
        $tags = $this->getParams('tags');
        $followers = $this->getParams('followers');

        if (empty($followers)) {
            throw new BadRequestHttpException('No specified follower selected');
        }

        if (Yii::$app->weConnect->addTagsToFollowers($channelId, $followers, $tags)) {
            return ['result' => 'success'];
        } else {
            throw new ServerErrorHttpException('edit tags failed!');
        }
    }

    /**
     * Remove tags for users
     *
     * <b>Request Method: </b>POST<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/channel/follower/add-tags<br/>
     * <b>Content-type: </b>application/json<br/>
     * <b>Summary: </b>This api is for add tags to followers<br/>
     *
     * <b>Request Parameters: </b><br/>
     *  followers: array[string], the ids of the followers.<br/>
     *  tags: array[string], the tags.
     * <b>Request Example: </b><br/>
     * {followers:["abasfdasfasdf", "fasfsavasgfw"], tags:["VIP", "new", "这些是XX"], channelId:""}
     *
     * <b>Response Example: </b><br/>
     * <pre>
     *  {"result":"success"}
     *   OR {"result":"fail"}
     * </pre>
     **/
    public function actionRemoveTags()
    {
        $channelId = $this->getChannelId();
        $tags = $this->getParams('tags');
        $followers = $this->getParams('followers');

        if (empty($followers)) {
            throw new BadRequestHttpException('No specified follower selected');
        }

        if (Yii::$app->weConnect->removeTags($channelId, $followers, $tags)) {
            return ['result' => 'success'];
        } else {
            throw new ServerErrorHttpException('edit tags failed!');
        }
    }

    /**
     * Add tags by query
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/follower/bulk-add-tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for add tags by query.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     tags: array, if more than one tag, implode by comma<br/>
     *     gender: MALE, FEMALE<br/>
     *     country: string<br/>
     *     province: string<br/>
     *     city: string<br/>
     *     subscribeTimeFrom: timestamp<br/>
     *     nickname: string<br/>
     *     addTags: array
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *
     * </pre>
     **/
    public function actionBulkAddTags()
    {
        $channelId = $this->getChannelId();
        $addTags = $this->getParams('addTags');
        if (empty($addTags)) {
            throw new BadRequestHttpException(Yii::t('member', 'tags_required'));
        }
        $params = $this->getParams();

        unset($params['channelId'], $params['addTags']);
        $jobArgs = ['channelId' => $channelId, 'add' => true, 'tags' => $addTags, 'query' => $params, 'description' => 'Direct: Add tags to followers batchly'];
        $result = Yii::$app->job->create('backend\modules\channel\job\UpdateTags', $jobArgs);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Fail to add tag by query');
        }
    }

    /**
     * Remove tags by query
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/follower/bulk-remove-tags<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for remove tags by query.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     tags: array, if more than one tag, implode by comma<br/>
     *     gender: MALE, FEMALE<br/>
     *     country: string<br/>
     *     province: string<br/>
     *     city: string<br/>
     *     subscribeTimeFrom: timestamp<br/>
     *     nickname: string<br/>
     *     removeTags: array
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *
     * </pre>
     **/
    public function actionBulkRemoveTags()
    {
        $channelId = $this->getChannelId();
        $removeTags = $this->getParams('removeTags');
        if (empty($removeTags)) {
            throw new BadRequestHttpException(Yii::t('member', 'tags_required'));
        }
        $params = $this->getParams();

        unset($params['channelId'], $params['removeTags']);
        $jobArgs = ['channelId' => $channelId, 'tags' => $removeTags, 'query' => $params, 'description' => 'Direct: Remove tags from followers batchly'];
        $result = Yii::$app->job->create('backend\modules\channel\job\UpdateTags', $jobArgs);

        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('Fail to add tag by query');
        }
    }

    public function actionFollowerTag()
    {
        $channelId = $this->getQuery('channelId');
        $userId = $this->getQuery('id');

        if (empty($channelId) && empty($userId)) {
            throw new BadRequestHttpException(Yii::t('channel', 'invalid_channel_id'));
        }
        return Yii::$app->weConnect->getFollowerById($userId, $channelId);
    }

    public function actionProperty()
    {
        $openId = $this->getQuery('openId');
        if (empty($openId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $member = Member::getByOpenId($openId);

        $properties = [];
        if (!empty($member)) {
            $member = $member->toArray();
            $properties = empty($member['properties']) ? [] : $member['properties'];
        } else {
            $accountId = $this->getAccountId();
            $follower = Follower::getByOpenId($accountId, $openId);
            if (!empty($follower)) {
                $follower = $follower->toArray();
                $properties = empty($follower['properties']) ? [] : $follower['properties'];
            }
        }

        return $properties;
    }
}
