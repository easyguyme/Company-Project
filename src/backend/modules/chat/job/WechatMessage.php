<?php
namespace backend\modules\chat\job;

use Yii;
use Exception;
use yii\web\BadRequestHttpException;
use backend\utils\LogUtil;
use backend\models\Token;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\ChatConversation;
use backend\modules\resque\components\ResqueUtil;
use backend\models\Follower;

class WechatMessage
{
    public function perform()
    {
        $args = $this->args;
        $message = $args['message'];
        $type = $message['msgType'];
        $content = $message['content'];
        $WEConnectAccountInfo = $args['account'];
        $WEConnectUserInfo = $args['user'];
        $accountId = new \MongoId($args['accountId']);
        $client = $this->generateClientInfo($accountId, $WEConnectAccountInfo, $WEConnectUserInfo);

        if (empty($type) || empty($content)) {
            $this->reportError('Miss type or content field for weconnect pushing', $client, $accountId);
            return;
        }

        if (empty($WEConnectAccountInfo)) {
            $this->reportError('Miss account information form weconnect', $client, $accountIdStr);
            return;
        }

        LogUtil::info([
            'message' => 'Get message from wechat',
            'WEConnectAccountInfo' => $WEConnectAccountInfo,
            'WEConnectUserInfo' => $WEConnectUserInfo,
            'accountId' => $accountId
        ], 'weconnect-webhook');

        try {
            if ('EVENT' === $type) {
                //Handle connect and disconnect event
                return call_user_func([$this, strtolower($content) . 'Handler'], $client, $accountId);
            } else {
                //Handle plain weconnect message
                $tuisongbao = Yii::$app->tuisongbao;
                $conversations = $tuisongbao->getConversations($client['openId']);
                if (empty($conversations)) {
                    $this->createChatUser($client, $accountId);
                    //Serve client without self helpdesk
                    return HelpDesk::connect($client, $accountId);
                }
                $helpdeskId = $conversations[0]['target'];
                $this->sendChatMessage($client, $helpdeskId, $content);
                return ['status' => 'ok'];
            }
        } catch (Exception $e) {
            $this->reportError($e->getMessage(), $client, $accountId);
        }
    }

    /**
     * Generate the client information structure
     * @param  MongoId $accountId         account UUID
     * @param  string  $channelName       channel name
     * @param  array   $WEConnectUserInfo weconenct user information
     * @return array   client information structure
     */
    private function generateClientInfo($accountId, $WEConnectAccountInfo, $WEConnectUserInfo)
    {
        $accountInfoType = null;
        $source = null;
        switch ($WEConnectAccountInfo['channel']) {
            case 'WEIBO':
                $source = ChatConversation::TYPE_WEIBO;
                $accountInfoType = 'WEIBO';
                break;
            case 'ALIPAY':
                $source = ChatConversation::TYPE_ALIPAY;
                $accountInfoType = 'ALIPAY';
                break;
            case 'WEIXIN':
                $source = ChatConversation::TYPE_WECHAT;
                $accountInfoType = $WEConnectAccountInfo['accountType'];
                break;
            default:
                throw new BadRequestHttpException("Unsupported channel type");
                break;
        }

        return [
            'nick' => $WEConnectUserInfo['nickname'],
            'avatar' => $WEConnectUserInfo['headerImgUrl'],
            'openId' => $WEConnectUserInfo['originId'],
            'source' =>  $source,
            'channelId' => $WEConnectUserInfo['accountId'],
            'accountId' => $accountId,
            'channelInfo' => [
                'type' => $accountInfoType,
                'name' => $WEConnectAccountInfo['name']
            ],
            'tags' => $WEConnectUserInfo['tags']
        ];
    }

    /**
     * Handle the connect event from weconnect
     * @param  array   $client    client information
     * @param  MongoId $accountId account UUID
     * @return mixed
     */
    private function connectHandler($client, $accountId)
    {
        $accountIdStr = (string) $accountId;
        $isInWorkingHour = HelpDeskSetting::isInWorkingHours($accountId);
        if ($isInWorkingHour) {
            $this->createChatUser($client, $accountIdStr);
            $conversations = Yii::$app->tuisongbao->getConversations($client['openId']);
            // check if there is a conversation exists
            if (!empty($conversations)) {
                LogUtil::info([
                    'message' => 'Have connect to helpdesk already',
                    'client' => $client,
                    'accountId' => $accountIdStr,
                    'conversations' => $conversations
                ], 'weconnect-webhook');
                HelpDesk::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_CUSTOM, ChatConversation::NO_DUPLICATE_CLIENT);
                return;
            }
            return HelpDesk::connect($client, $accountId);
        } else {
            //send the disconnect event to WeConnect
            HelpDesk::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_NONWORKING);
            Yii::$app->weConnect->sendCustomerServiceMessage($client['openId'], $client['channelId'], ['msgType' => ChatConversation::WECHAT_MESSAGE_TYPE_EVENT, 'content' => 'DISCONNECT']);
        }
    }

    /**
     * Handle the disconenct event from weconnect
     * @param  array   $client    client information
     * @param  MongoId $accountId account UUID
     * @return mixed
     */
    private function disconnectHandler($client, $accountId)
    {
        LogUtil::info(['message' => 'Start to disconnect user'], 'weconnect-webhook');
        //get the conversationId
        $conversations = Yii::$app->tuisongbao->getConversations($client['openId']);
        if (empty($conversations)) {
            return ['status' => 'ok'];
        }
        $conversationId = $conversations[0]['conversationId'];
        $helpdeskId = $conversations[0]['target'];

        //disconnect
        HelpDesk::weconnectClientLeave($client, $helpdeskId, $conversationId, ['type' => 'brake']);
        LogUtil::info(['message' => 'Disconnected user'], 'weconnect-webhook');
    }

    /**
     * Create chat user for tuisongbao
     * @param  array   $client    client information
     * @param  MongoId $accountId account UUID
     * @return mixed
     */
    private function createChatUser($client, $accountId)
    {
        LogUtil::info(['message' => 'Start to create chat user for tuisongbao'], 'weconnect-webhook');
        $result = Yii::$app->tuisongbao->createChatUser($client['openId'], $client['nick']);
        if (!$result) {
            $this->reportError('Fail to create user for tuisongbao', $client, $accountId);
        }
    }


    /**
     * Send chat message to tuisongbao
     * @param  array   $client     client information
     * @param  string  $helpdeskId helpdesk UUID
     * @param  string  $content    chat text message
     */
    private function sendChatMessage($client, $helpdeskId, $content)
    {
        //trigger send message event
        $data = [
            'accountId' => (string) $client['accountId'],
            'source' => $client['source'],
            'nick' => $client['nick'],
            'channelId' => $client['channelId'],
            'avatar' => $client['avatar'],
            'isHelpdesk' => false
        ];
        Yii::$app->tuisongbao->sendChatMessage($client['openId'], $helpdeskId, $content, $data);

        // Send wechatcp message if this helpdesk is away from WeiXin broswer.
        $this->sendWechatCpMessage($client, $helpdeskId, $content);
    }

    /**
     * Send wechat corp message to helpdesk app.
     * @param  array   $client     client information
     * @param  string  $helpdeskId helpdesk UUID
     * @param  string  $content    chat text message
     */
    private function sendWechatCpMessage($client, $helpdeskId, $content)
    {
        $helpdesk = HelpDesk::getById(new \MongoId($helpdeskId));
        $token = Token::getLastestByUserId(new \MongoId($helpdeskId));
        LogUtil::info(['message' => 'Check helpdesk app on-state', 'helpdeskId' => $helpdeskId, 'tokenId' => (string) $token->_id], 'wechatcp');
        // if token's isOnline is false and then send wechat corp message.
        if ($token->isOnline === false) {
            $badge = $helpdesk->badge;
            $helpDeskSetting = HelpDeskSetting::getInstance($client['accountId']);
            $corpId = $helpDeskSetting->wechatcp['corpId'];
            $agentId = $helpDeskSetting->wechatcp['agentId'];
            if (empty($corpId) || empty($agentId)) {
                throw new BadRequestHttpException("Parameters missing");
            }
            $message = [
                'touser' => $badge,
                'msgtype' => 'text',
                'agentid' => $agentId,
                'text' => ['content' => $content]
            ];
            LogUtil::info(['message' => 'Start to send wechatcp message', 'corpId' => $corpId, 'data' => $message], 'wechatcp');
            Yii::$app->weConnect->sendWechatCpMessage($corpId, $message);
        }
    }

    /**
     * Send chat message to tuisongbao
     * @param  string  $message    error message
     * @param  array   $client     client information
     * @param  MongoId $accountId  account UUID
     */
    private function reportError($message, $client, $accountId)
    {
        $data = [
            'message' => $message,
            'client' => $client,
            'accountId' => $accountId
        ];
        LogUtil::error($data, 'weconnect-webhook');
        ResqueUtil::log($data);
        HelpDesk::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_ERROR);
    }
}
