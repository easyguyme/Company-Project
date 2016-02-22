<?php
namespace backend\modules\common\controllers;

use backend\models\Token;
use backend\models\Account;
use Yii;
use yii\web\BadRequestHttpException;
use backend\behaviors\ChannelBehavior;
use backend\models\Channel;

class ChannelController extends BaseController
{

    /**
     * Query channels which is status is enable.
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/channels<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying channels that the status is enable.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the query result, 0 means query fail, 1 means query successfully<br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     data: array, json array to queried channels detail information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    'data' :
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $result = array();
        $accountId = $this->getAccountId();

        $this->attachBehavior('ChannelBehavior', new ChannelBehavior());
        return $this->syncAccountChannels($accountId);
    }

    /**
     * Query all channels
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/channel/channels-all<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying all channels.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     ack: integer, mark the query result, 0 means query fail, 1 means query successfully<br/>
     *     msg: string, if query fail, it contains the error message<br/>
     *     data: array, json array to queried channels detail information<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * [
     *    {
     *        "id": "55ba03f6e9c2fbf3348b4567",
     *        "channelId": "54d9c155e4b0abe717853ee1",
     *        "origin": "wechat",
     *        "name": "熊猫Baby",
     *        "type": "SERVICE_AUTH_ACCOUNT",
     *        "status": "disable",
     *        "isTest": true
     *     }
     * ]
     * </pre>
     */
    public function actionChannelsAll()
    {
        $accountId = $this->getAccountId();
        return  Channel::getAllByAccount($accountId);
    }

    /**
     * Query all menu actions
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/channel/menu-action<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying all menu actions.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Request Example:</b><br/>
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     "member": {
     *         "title": "channel_menu_member",
     *         "keycode": "USER_CENTER",
     *         "actionKey": "channel_menu_member_title",
     *         "msgType": "URL",
     *         "content": "http://wm.com/api/mobile/member?appId=wx2df5d7e4ce8a04ca&channelId=54d9c155e4b0abe717853ee1"
     *     },
     *     "helpdesk": {
     *         "title": "helpdesk",
     *         "keycode": "CUSTOMER_SERVICE",
     *         "actionKey": "channel_menu_helpdesk_title",
     *         "msgType": "",
     *         "content": false
     *     }
     * }
     * </pre>
     */
    public function actionMenuAction()
    {
        $channelId = $this->getQuery('channelId');
        $accountId = Token::getAccountId();
        return Yii::$app->channelMenu->getMenuActions($channelId, $accountId);
    }

    /**
     * get the channel info by channelId
     */
    public function actionGetChannelInfo()
    {
        $params = $this->getQuery();
        if (empty($params['channelId'])) {
            throw new BadRequestHttpException('missing params');
        }
        return Yii::$app->weConnect->getAccounts($params['channelId']);
    }
}
