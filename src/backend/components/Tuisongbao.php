<?php

namespace backend\components;

use yii\base\Component;
use backend\exceptions\ApiDataException;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;
use Yii;
use backend\utils\LogUtil;
use backend\utils\StringUtil;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\ChatConversation;

/**
 * Class file for Tuisongbao.
 * @author DevinJin.
 */
class Tuisongbao extends Component
{
    public $domain;
    public $appId;
    public $secret;

    public $pushAppId;
    public $pushSecret;

    //const for method
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_GET = 'get';
    const METHOD_DELETE = 'delete';

    const GROUP_CHAT = 'groupChat';
    const SINGLE_CHAT = 'singleChat';

    const CLIENT_ROLE = 'client';
    const HELPDESK_ROLE = 'helpdesk';

    const CONNECT_MESSAGE = '有新的咨询';
    const LEAVE_MESSAGE = '当前用户已离线';

    /**
     * Initializer
     */
    public function init()
    {

    }

    /**
     * trigger event
     * @param string $name
     * @param array $data
     * @param array $channels
     * @param array $excludedSocketId
     */
    public function triggerEvent($name, $data, $channels, $excludedSocketId = "")
    {
        $param = [
            "name" => $name,
            "data" => $data,
            "channels" => $channels,
        ];

        if (!empty($excludedSocketId)) {
            $param['excludedSocketId'] = $excludedSocketId;
        }
        $url = $this->domain . '/v2/open/engine/events';
        $result = $this->requestService($url, self::METHOD_POST, $param);

        if ($result != 'OK') {
            throw new ApiDataException($url, $result, $param, 'chat');
        }
    }

    /**
     * Push open message by target
     * @param Array $taget
     * @param string $message
     * @throws ApiDataException
     */
    public function pushMessage($taget, $badge, $extra, $message)
    {
        $url = $this->domain . '/v2/open/messages';

        $content['ttl'] = \Yii::$app->params['PUSH_MESSAGE_MAX_SAVE_TIME'];
        $content['extra'] = $extra;
        if ($message !== null) {
            $content['alert'] = $message;
            $content['apns'] = [
                'badge' => $badge,
                'sound' => 'default',
                'content-available' => 1
            ];
        } else {
            $content['apns'] = [
                'content-available' => 1
            ];
        }

        $params = [
            'content' => $content,
            'target'  => ['tokens' => $taget],
            'trigger' => ['now' => true]
        ];

        $resultJson = $this->requestService($url, self::METHOD_POST, $params, true);

        if (StringUtil::isJson($resultJson)) {
            $result = Json::decode($resultJson);
        } else {
            throw new ApiDataException($url, $resultJson, $params, 'tuisongbao');
        }

        if (empty($result['nid'])) {
            throw new ApiDataException($url, $result, $params, 'tuisongbao');
        }
    }

    /**
     * Get the information for a channel
     * @param string $channelName the name of the channel
     */
    public function getChannelInfo($channelName)
    {
        if (empty($channelName)) {
            throw new BadRequestHttpException('Channel name can not be empty');
        }
        $url = $this->domain . '/v2/open/engine/channels/' . $channelName;
        $result = $this->requestService($url, self::METHOD_GET);
        return $result;
    }

    /**
     * Get the user list for a channel
     * @param string $channelName the name of the channel
     */
    public function getChannelUsers($channelName)
    {
        if (empty($channelName)) {
            throw new BadRequestHttpException('Channel name can not be empty');
        }
        $url = $this->domain . '/v2/open/engine/channels/' . $channelName . '/userIds';
        $result = $this->requestService($url, self::METHOD_GET);
        return $result;
    }

    /**
     * Create chat user mainly for wechat user
     * @param  string $userId user id (required)
     * @return array response body
     */
    public function createChatUser($userId, $nickname)
    {
        $params = ['userId' => $userId, 'nickname' => $nickname];
        $url = $this->domain . '/v2/open/engine/chat/users';
        $resultJson = $this->requestService($url, self::METHOD_PUT, $params);
        $result = $this->getData($resultJson, 'isNew', 'create chat user');
        return isset($result['isNew']);
    }

    /**
     * Get conversation list
     * @param  string $userId         user id (required)
     * @param  string $deskId         helpdesk id (optional)
     * @param  string $conversationId conversation id (optional)
     * @param  string $lastActiveAt   only filter active conversations after the time (optional)
     * @return array conversation list
     */
    public function getConversations($userId, $deskId = '', $conversationId = '', $lastActiveAt = '', $active = true)
    {
        $params = ['type' => self::SINGLE_CHAT];
        !empty($conversationId) && ($params['conversationId'] = $conversationId);
        !empty($deskId) && ($params['target'] = $deskId);
        !empty($lastActiveAt) && ($params['lastActiveAt'] = $lastActiveAt);
        if (!$active) {
            $params['active'] = 'false';
        }

        $url = $this->domain . '/v2/open/engine/chat/users/'. $userId . '/conversations';
        $resultJson = $this->requestService($url, self::METHOD_GET, $params);
        return Json::decode($resultJson);
    }

    /**
     * Get group list
     * @param  string $userId         user id (required)
     * @param  string $groupId        group id (optional)
     * @param  string $lastActiveAt   only filter active conversations after the time (optional)
     * @return array conversation list
     */
    public function getGroups($userId, $groupId = '', $lastActiveAt = '')
    {
        $params = [];
        !empty($groupId) && ($params['groupId'] = $groupId);
        !empty($lastActiveAt) && ($params['lastActiveAt'] = $lastActiveAt);

        $url = $this->domain . '/v2/open/engine/chat/users/'. $userId . '/groups';
        $resultJson = $this->requestService($url, self::METHOD_GET, $params);
        return Json::decode($resultJson);
    }

    /**
     * Create chat conversation for group chat type
     * @param  string $helpdeskId  desk id
     * @param  string $userId  user id
     * @return string conversation id
     */
    public function createConversation($client, $helpdeskId)
    {
        $client['accountId'] = (string) $client['accountId'];
        $helpdesk = HelpDesk::findByPk(new \MongoId($helpdeskId));
        $params = [
            'type' => self::SINGLE_CHAT,
            'peers' => [$helpdeskId, $client['openId']],
            'extra' => [
                'helpdesk' => [
                    'id' => $helpdeskId,
                    'nick' => $helpdesk->name,
                    'badge' => $helpdesk->badge
                ],
                'client' => $client
            ],
            'webHook' => [
                'eventTypes' => ['message_new'],
                'url' => DOMAIN . 'api/chat/conversation/message-webhook',
                'status' => 'enabled'
            ]
        ];

        $url = $this->domain . '/v2/open/engine/chat/conversations';
        $resultJson = $this->requestService($url, self::METHOD_PUT, $params);
        $result = $this->getData($resultJson, 'conversationId', 'create conversation');
        return $result['conversationId'];
    }

    /**
     * Get message list of a conversation
     * @param  string  $conversationId conversation id (required)
     * @param  integer $startMessageId (optional)
     * @param  integer $endMessageId   (optional)
     * @param  integer $limit          message count (optional)
     * @return array message list
     */
    public function getMessages($conversationId, $startMessageId = 0, $endMessageId = 0, $limit = 0)
    {
        $params = [];
        !empty($startMessageId) && ($params['startMessageId'] = $startMessageId);
        !empty($endMessageId) && ($params['endMessageId'] = $endMessageId);
        !empty($limit) && ($params['limit'] = $limit);

        $url = $this->domain . '/v2/open/engine/chat/conversations/'. $conversationId . '/messages';
        $resultJson = $this->requestService($url, self::METHOD_GET, $params);
        return Json::decode($resultJson);
    }

    /**
     * Get user list of a group
     * @param  string  $groupId group id (required)
     * @return array user list
     */
    public function geGrouptUsers($groupId)
    {
        $url = $this->domain . '/v2/open/engine/chat/groups/'. $groupId . '/users';
        $resultJson = $this->requestService($url, self::METHOD_GET);
        return Json::decode($resultJson);
    }

    /**
     * Create chat group for user and helpdesk
     * @param  string $userId
     * @param  string $helpdeskId
     * @return string group Id
     */
    public function createGroup($userId, $helpdeskId)
    {
        $params = [
            'owner' => $userId,
            'inviteUserIds'  => [$helpdeskId]
        ];

        $url = $this->domain . '/v2/open/engine/chat/groups';
        $resultJson = $this->requestService($url, self::METHOD_POST, $params);
        $result = $this->getData($resultJson, 'groupId', 'create group');
        return $result['groupId'];
    }

    /**
     * Delete conversation
     * @param string $helpdeskId
     * @param string $clientId
     * @return boolean
     */
    public function removeConversation($userId, $targetId)
    {
        $params = ['type' => self::SINGLE_CHAT];

        $url = $this->domain . '/v2/open/engine/chat/conversations';
        $params['peers'] = [$userId, $targetId];
        $resultJson = $this->requestService($url, self::METHOD_DELETE, $params);
        if ($resultJson == 'OK') {
            return true;
        }
        return false;
    }

    /**
     * Send chat mesage to tuisongbao
     * @param  string $from    logined user id
     * @param  string $to      logined user id
     * @param  array $content the content to be sent
     * @return string message id
     */
    private function sendMessage($from, $to, $content)
    {
        $params = [
            'type' => self::SINGLE_CHAT,
            'from' => $from,
            'to'  => $to,
            'content' => $content
        ];

        $url = $this->domain . '/v2/open/engine/chat/messages';
        $resultJson = $this->requestService($url, self::METHOD_POST, $params);
        $message = Json::decode($resultJson);
        if (empty($message['messageId'])) {
            throw new ApiDataException($url, $message, $params, 'tuisongbao');
        }

        return $message['messageId'];
    }

    /**
     * Notify the user and helpdesk a new user joined
     * @param  $client the client information, containing openId, nick, avatar
     * @param  string $deskId helpdesk id
     */
    public function notifyClientJoined($client, $helpdeskId, $conversationId, $accountId)
    {
        // It is triggered by client
        $content = [
            'type' => 'text',
            'text' => self::CONNECT_MESSAGE,
            'extra' => [
                'action' => ChatConversation::JOIN_ACTION,
                'client' => $client,
                'helpdeskId' => $helpdeskId,
                'conversationId' => $conversationId,
                'accountId' => (string) $accountId,
                'isHelpdesk' => false
            ]
        ];
        return $this->sendMessage($client['openId'], $helpdeskId, $content);
    }

    /**
     * Notify the user and helpdesk a new user joined
     * @param  $client the client information, containing openId, nick, avatar
     * @param  string $deskId helpdesk id
     */
    public function notifyClientLeft($client, $helpdeskId, $conversationId, $accountId, $isHelpdesk = false)
    {
        // It is triggered by client
        $content = [
            'type' => 'text',
            'text' => self::LEAVE_MESSAGE,
            'extra' => [
                'action' => ChatConversation::LEAVE_ACTION,
                'client' => $client,
                'helpdeskId' => $helpdeskId,
                'conversationId' => $conversationId,
                'accountId' => (string) $accountId,
                'isHelpdesk' => $isHelpdesk
            ]
        ];
        if ($isHelpdesk) {
            return $this->sendMessage($helpdeskId, $client['openId'],  $content);
        }
        return $this->sendMessage($client['openId'], $helpdeskId, $content);
    }

    /**
     * Notify the user that helpdesk transfer the conversation to another helpdesk
     * @param  string $openId the open id for client
     * @param  string $deskId the helpdesk id
     * @param  string $data transfer message related data
     */
    public function notifyClientTransfered($openId, $helpdeskId, $data, $accountId)
    {
        // It is triggered by helpdesk
        $data = array_merge([
            'action' => ChatConversation::TRANSFER_ACTION,
            'accountId' => (string) $accountId,
            'isHelpdesk' => false
        ], $data);
        $content = [
            'type' => 'text',
            'text' => self::CONNECT_MESSAGE,
            'extra' => $data
        ];
        return $this->sendMessage($openId, $helpdeskId, $content);
    }

    /**
     * Notify the user that helpdesk leave the conversation
     * @param  string $openId the open id for client
     * @param  string $deskId the helpdesk id
     * @param  string $text chat message text
     * @param  string $data transfer message related data
     */
    public function sendChatMessage($openId, $helpdeskId, $text, $data)
    {
        if (empty($data['accountId'])) {
            throw new BadRequestHttpException('Lack of account id for chat message');
        }
        // It is triggered by helpdesk
        $data = array_merge([
            'action' => ChatConversation::CHAT_ACTION,
            'isHelpdesk' => false
        ], $data);
        $content = [
            'type' => 'text',
            'text' => $text,
            'extra' => $data
        ];
        return $this->sendMessage($openId, $helpdeskId, $content);
    }

    /**
     * Request the service with basic auth
     * @param string $url requested url
     * @param string $method requested method
     * @param string $param requested parameters
     * @param boolean $isPushService requested parameters
     */
    private function requestService($url, $method, $param = null, $isPushService = false)
    {
        if ($isPushService) {
            $basicAuth = base64_encode($this->pushAppId . ':' . $this->pushSecret);
        } else {
            $basicAuth = base64_encode($this->appId . ':' . $this->secret);
        }
        $headers = ['Content-Type:application/json', 'Authorization:Basic ' . $basicAuth];

        LogUtil::info([
            'message' => 'Send request to tuisongbao service',
            'url' => $url,
            'method' => $method,
            'param' => $param,
            'headers' => $headers,
            'isPushService' => $isPushService
        ], 'chat');

        $curl = Yii::$app->curl->setHeaders($headers);

        if (empty($param)) {
            $param = [];
        }

        if ($method === self::METHOD_GET) {
            $result = $curl->$method($url, $param, false);
        } else if ($method === self::METHOD_DELETE) {
            $result = $curl->$method($url, $param, 'json');
        } else {
            $param = Json::encode($param);
            $result = $curl->$method($url, $param);
        }

        LogUtil::info([
            'message' => 'Got response from tuisongbao service',
            'url' => $url,
            'method' => $method,
            'param' => $param,
            'result' => $result,
            'isPushService' => $isPushService
        ], 'chat');

        return $result;
    }

    /**
     * Get JSON data from response
     * @param  string $resultJson JSON data
     * @param  string $fieldName  Field name need to be checked
     * @param  string $action     action description
     * @return array  the array data decode from JSON string
     */
    private function getData($resultJson, $fieldName, $action)
    {
        $result = Json::decode($resultJson);
        if (!isset($result[$fieldName])) {
            LogUtil::error([
                'message'=>'Fail to ' . $action,
                'cause' => $resultJson
            ], 'chat');
        }
        return $result;
    }
}
