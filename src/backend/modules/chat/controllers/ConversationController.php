<?php
namespace backend\modules\chat\controllers;

use Yii;
use MongoId;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\SelfHelpDeskSetting;
use backend\modules\helpdesk\models\HelpDesk;
use backend\modules\helpdesk\models\ChatConversation;
use backend\modules\helpdesk\models\ChatMessage;
use backend\models\PendingClient;
use backend\models\Token;
use backend\modules\member\models\Member;
use backend\utils\LogUtil;
use backend\utils\TimeUtil;
use backend\modules\helpdesk\models\Statistics;
use backend\models\Graphic;
use backend\modules\helpdesk\models\ChatSession;

class ConversationController extends Controller
{
    /**
     * Recieve message from weconnect
     *
     * <b>Request Type: </b>POST<br/>
     * <b>Content-Type: </b>application/json<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/chat/conversation/wechat-message<br/>
     * <b>Summary: </b>This api is for recieving a message from weconnect<br/>
     *
     * <b>Request Parameters: </b><br/>
     *     message: object, the content for the message<br/>
     *         message.msgType: string, "text"<br/>
     *         message.content: mixed, the body of the content<br/>
     *     account: object, the account information<br/>
     *     user: object, the user information<br/>
     *     userId: string, the wechat user id<br/>
     *
     * <b>Response Example</b>
     * <pre>{"status":"ok"}</pre>
     */
    public function actionWechatMessage()
    {
        $message = $this->getParams('message');
        $type = $message['msgType'];
        $content = $message['content'];
        $WEConnectAccountInfo = $this->getParams('account');
        $WEConnectUserInfo = $this->getParams('user');
        $accountId = $this->getAccountId();

        $args = [
            'message' => $message,
            'account' => $WEConnectAccountInfo,
            'user' => $WEConnectUserInfo,
            'accountId' => (string) $accountId
        ];

        //Serve client if he is in self helpdesk mode
        $redis = \Yii::$app->cache->redis;
        $modeKey = SelfHelpDeskSetting::CONVERSATION_MODE_PREFIX . $accountId . '-' . $WEConnectUserInfo['originId'];
        if (SelfHelpDeskSetting::exists($accountId)) {
            $isSelfHelpdeskMode = $redis->get($modeKey);
            //$isHelpdesk will be empty the first time
            if (empty($isSelfHelpdeskMode) || $isSelfHelpdeskMode != 'false') {
                SelfHelpDeskSetting::autoReply($args);
                LogUtil::info(['message' => 'Serve client in self helpdesk mode'], 'chat');
                return ['status' => 'ok'];
            }
        }
        $redis->set($modeKey, 'false', 'EX', SelfHelpDeskSetting::EXPIRED_TIME);

        //Create job to handle normal helpdesk request
        LogUtil::info(['message' => 'Serve client in normal helpdesk mode in schedule job'], 'chat');
        Yii::$app->job->create('backend\modules\chat\job\WechatMessage', $args);

        return ['status' => 'ok'];
    }

    /**
     * Tranfer client to another helpdesk
     *
     * <b>Request Type: </b>POST<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/chat/conversation/transfer
     * <b>Summary: </b> This api is for transfer helpdesk.<br/>
     *
     * <b>Request Parameters: </b><br/>
     *     accesstoken: string<br/>
     *     helpdesk: object, helpdesk with id, avatar, badge fields.<br/>
     *     targetHelpdesk: object, helpdesk with id, avatar, badge fields.<br/>
     *     client: object, client with openId, nick, avatar fields.<br/>
     *
     * <b>Response Example: </b><br/>
     * {
     *     "status": "ok"
     * }
     */
    public function actionTransfer()
    {
        $cache = Yii::$app->cache;
        $params = $this->getParams();
        $originalHelpdesk = $params['helpdesk'];
        $targetHelpdesk = $params['targetHelpdesk'];
        $client = $params['client'];
        $accountId = $this->getAccountId();
        $openId = $client['openId'];
        $originalHelpdeskId = $originalHelpdesk['id'];
        $targetHelpdeskId = $targetHelpdesk['id'];

        $conversations = ChatConversation::getConversationMap($accountId);
        empty($client['accountId']) && ($client['accountId'] = $accountId);

        $activities = ChatConversation::getActivityMap($accountId);
        if (empty($activities) || !isset($activities[$openId])) {
            throw new BadRequestHttpException(Yii::t('chat', 'customer_offline'));
        }

        if (!isset($activities[$targetHelpdeskId])) {
            throw new BadRequestHttpException(Yii::t('chat', 'helpdesk_offline'));
        }

        if ($conversations) {
            //Check target clients amount
            $maxClientLimit = HelpDeskSetting::getMaxClientCount($accountId);
            if (empty($conversations[$targetHelpdeskId])) {
                $conversations[$targetHelpdeskId] = [];
            }
            $targetClients = $conversations[$targetHelpdeskId];
            if (!empty($targetClients) && count($targetClients) >= $maxClientLimit) {
                throw new BadRequestHttpException(Yii::t('chat', 'helpdesk_exceed_capacity'));
            }

            // Add to transfered desk client list (cache and count)
            $targetHelpdeskMongoId = new MongoId($targetHelpdeskId);
            $inLimited = HelpDesk::incClientCount($targetHelpdeskMongoId, $maxClientLimit);
            if (!$inLimited) {
                throw new BadRequestHttpException(Yii::t('chat', 'helpdesk_exceed_capacity'));
            }

            // Remove the client from original desk client list (cache and count)
            $originalHelpdeskMongoId = new MongoId($originalHelpdeskId);
            $conversations[$originalHelpdeskId] = HelpDesk::removeClient($originalHelpdeskMongoId, $openId, $conversations[$originalHelpdeskId]);


            $conversations[$targetHelpdeskId][] = $openId;
            ChatConversation::setConversationMap($accountId, $conversations);

            // Create chat conversation and notify transfering
            $conversationId = Yii::$app->tuisongbao->createConversation($client, $targetHelpdeskId);
            $data = $this->generateTransferData($client, $originalHelpdesk, $targetHelpdesk, $conversationId);
            Yii::$app->tuisongbao->notifyClientTransfered($openId, $targetHelpdeskId, $data, $accountId);

            LogUtil::info([
                'message' => 'Transfer helpdesk',
                'targetHelpdeskId' => $targetHelpdeskId,
                'originalHelpdeskId' => $originalHelpdeskId,
                'openId' => $openId
            ], 'chat');
            return ['status' => 'ok'];
        }
    }

    /**
     * Get the conversation list for one user
     * @return array conversation list
     */
    public function actionIndex()
    {
        $params = $this->getQuery();
        if (empty($params['openId'])) {
            throw new BadRequestHttpException('Lack of openId');
        }
        //return Yii::$app->tuisongbao->getConversations($openId);
        $accountId = $this->getAccountId();
        return ChatSession::search($accountId, $params, true);
    }

    /**
     * Get the messages for one conversation
     * @return array conversation list
     */
    public function actionMessages()
    {
        $conversationId = $this->getQuery('conversationId');
        $startMessageId = $this->getQuery('startMessageId', 0);
        $endMessageId = $this->getQuery('endMessageId', 0);
        $limit = $this->getQuery('limit', 0);
        if (empty($conversationId)) {
            throw new BadRequestHttpException('Lack of conversationId');
        }
        return Yii::$app->tuisongbao->getMessages($conversationId, $startMessageId, $endMessageId, $limit);
    }

    /**
     * Handle tuisongbao use presence event
     * Reference: http://www.tuisongbao.com/docs/engineGuide/webHook##%23%23web-hook
     * @return [type] [description]
     */
    public function actionUserPresence()
    {
        $body = $this->getWebhookBody();
        if (!empty($body)) {
            $time = $body['timestamp'];
            $event = $body['events'][0];
            $helpdeskId = $event['userId'];
            $eventName = $event['name'];
            //Only handle global channel event and helpdesk event
            if (strpos($event['channel'], ChatConversation::CHANNEL_GLOBAL) !== false
             && MongoId::isValid($helpdeskId)) {
                $helpdesk = HelpDesk::findByPk(new \MongoId($helpdeskId));
                //If it is existing helpdesk
                if (!empty($helpdesk)) {
                    $accountId = (string) $helpdesk->accountId;
                    $activities = ChatConversation::getActivityMap($accountId);
                    LogUtil::info([
                        'message' => 'Got helpdesk online or offline event',
                        'timestamp' => $time
                    ], 'tuisongbao-webhook');
                    if ($eventName == ChatConversation::EVENT_USER_ADDED) {
                        //Update cache indicate that the helpdesk is online
                        HelpDesk::join($helpdeskId, $accountId, $time);
                        LogUtil::info(['message' => 'Helpdesk is online'], 'tuisongbao-webhook');
                    }
                    // Handle helpdesk who has been online
                    if (isset($activities[$helpdeskId])) {
                        // Handle use_removed and skip retrying request based on timestamp (retrying timestamp remains unchanged)
                        if ($eventName == ChatConversation::EVENT_USER_REMOVED
                         && $activities[$helpdeskId] < $time) {
                            // Zero the helpdesk active time in case that his socket is closed
                            $token = Token::getLastestByUserId(new \MongoId($helpdeskId));
                            if (isset($token->isOnline)) {
                                $token->isOnline = false;
                                $token->save(true, ['isOnline']);
                            } else {
                                ChatConversation::zeroActiveTime($helpdeskId, $accountId);
                                LogUtil::info(['message' => 'Helpdesk is offline'], 'tuisongbao-webhook');
                            }
                        }
                    }
                    unset($activities);
                }
            }
        }
    }

    /**
     * Handle tuisongbao conversation webhook
     */
    public function actionMessageWebhook()
    {
        $body = $this->getWebhookBody();
        if (!empty($body)) {
            $time = $body['timestamp'];
            $event = $body['events'][0];
            $eventName = $event['name'];
            $message = $event['message'];
            $message['conversationId'] = $event['conversationId'];
            //Handle mesage_new event
            if (ChatConversation::NEW_MESSAGE_EVENT == $eventName && !empty($message)) {
                $action = $message['content']['extra']['action'];
                //Handle based on action name
                call_user_func([$this, $action . 'Handler'], $message);
                LogUtil::info([
                    'message' => 'Got message_new message from tuisongbao webhook',
                    'clientId' => $clientId,
                    'action' => $action
                ], 'tuisongbao-webhook');
            }
        }
    }

    private function joinHandler($message)
    {
        $data = $message['content']['extra'];
        $accountId = new MongoId($data['accountId']);
        LogUtil::info(['message' => 'Got join message', 'data' => $data], 'tuisongbao-webhook');

        //Update totalConversation for conversationStatistics
        Statistics::incrTotalConversation($accountId);

        //Update totalUser  for conversationStatistics
        Statistics::statsUser($message['from'], $accountId);

        //Update the activity cache for client, in case client does not talk
        $this->updateUserActivity($message);

        //Record chatSession
        $deskId = $data['helpdeskId'];
        $helpDesk =  HelpDesk::findOne(['_id' => new MongoId($deskId)]);
        if (!empty($helpDesk)) {
            $desk = [
                'avatar' => $helpDesk->avatar,
                'badge'  => $helpDesk->badge,
                'email'  => $helpDesk->email,
                'name'   => $helpDesk->name,
                'id'     => $helpDesk->_id
            ];
            ChatSession::recordChatSession($message['conversationId'], $data['client'], $desk, $data['accountId'], $message['messageId']);
        }
    }

    private function leaveHandler($message)
    {
        $data = $message['content']['extra'];
        $accountId = $data['accountId'];
        LogUtil::info(['message' => 'Got leave message', 'data' => $data], 'tuisongbao-webhook');
        if ($data['isHelpdesk']) {
            //Helpdesk endsession
            $helpdeskId = $message['from'];
            $clientId = $message['to'];
            Yii::$app->tuisongbao->removeConversation($helpdeskId, $clientId);
            Yii::$app->tuisongbao->removeConversation($clientId, $helpdeskId);

            if (!empty($data['client']['channelId'])) {
                $client = [
                    'openId' => $clientId,
                    'channelId' => $data['client']['channelId']
                ];
                HelpDesk::notifyWeonnectDisconnent($accountId, $client, $helpdeskId, HelpDeskSetting::REPLY_CLOSE);
            }
        } else {
            //Client leave
            $helpdeskId = $message['to'];
            $clientId = $message['from'];
            Yii::$app->tuisongbao->removeConversation($clientId, $helpdeskId);
        }
        ChatConversation::removeUser($clientId, $accountId, false, $helpdeskId);
        // from field is helpdesk id
        Helpdesk::connectPendingClient($helpdeskId, $accountId);

        //Update chatSession endMessageId
        ChatSession::updateEndMessageId($accountId, $message['conversationId'], $message['messageId'], $message['createdAt']);
    }

    private function chatHandler($message)
    {
        $data = $message['content']['extra'];
        LogUtil::info(['message' => 'Got chat message', 'data' => $data], 'tuisongbao-webhook');
        // Handle the message sent from helpdesk to wechat user
        if ($data['isHelpdesk'] && !empty($data['targetChannel'])) {
            $messageData = [
                'msgType' => 'TEXT',
                'content' => $message['content']['text'],
            ];
            if (!empty($data['type']) && $data['type'] == 'article') {
                $graphic = Graphic::findByPk(new MongoId($message['content']['text']));
                if (!empty($graphic)) {
                    $messageData = [
                        'msgType' => 'NEWS',
                        'content' => ['articles' => $graphic->articles],
                    ];
                }
            }
            $messageData['createTime'] = TimeUtil::msTime(strtotime($message['createdAt']));

            //Set customer account for helpdesk demo
            if ($data['isHelpdesk']) {
                $helpdesk = HelpDesk::getById(new MongoId($message['from']));
                if (!empty($helpdesk) && isset($helpdesk->kfAccount)) {
                    $messageData['customerServiceAccount'] = [
                        'kfAccount' => $helpdesk->kfAccount
                    ];
                }
            }

            //send weconnect message by openId
            Yii::$app->weConnect->sendCustomerServiceMessage($message['to'], $data['targetChannel'], $messageData);
        }
        //Update the activity cache for helpdesk and client
        $this->updateUserActivity($message);

        //Update totalMessage for conversationStatistics
        $accountId = new MongoId($data['accountId']);
        Statistics::incrTotalMessage($accountId);
    }

    private function transferHandler($message)
    {
        $data = $message['content']['extra'];
        LogUtil::info(['message' => 'Got transfer message', 'data' => $data], 'tuisongbao-webhook');
        $accountId = new MongoId($data['accountId']);

        //Update totalConversation for conversationStatistics
        Statistics::incrTotalConversation($accountId);

        //Create a new chatSession
        $deskId = $data['targetHelpdesk']['id'];
        $helpDesk =  HelpDesk::findOne(['_id' => new MongoId($deskId)]);
        if (!empty($helpDesk)) {
            LogUtil::info(['helpDesk' => $helpDesk->toArray()], 'helpdesk');
            $desk = [
                'avatar' => $helpDesk->avatar,
                'badge'  => $helpDesk->badge,
                'email'  => $helpDesk->email,
                'name'   => $helpDesk->name,
                'id'     => $helpDesk->_id
            ];
            ChatSession::recordChatSession($message['conversationId'], $data['client'], $desk, $data['accountId'], $message['messageId']);
        }
    }

    private function beforeTransferHandler($message)
    {
        $data = $message['content']['extra'];
        $accountId = new MongoId($data['accountId']);

        ChatSession::updateEndMessageId($accountId, $message['conversationId'], $message['messageId'], $message['createdAt']);

        $helpdeskId = $message['from'];
        $clientId = $message['to'];
        Yii::$app->tuisongbao->removeConversation($helpdeskId, $clientId);
        Yii::$app->tuisongbao->removeConversation($clientId, $helpdeskId);
    }

    /**
     * Send transfer message to target helpdesk
     * @param  array $client           [description]
     * @param  array $originalHelpdesk [description]
     * @param  string $conversationId   [description]
     */
    private function generateTransferData($client, $originalHelpdesk, $targetHelpdesk, $conversationId)
    {
        $openId = $client['openId'];
        $lastChatTime = null;
        $chatTimes = 0;
        $conversations = Yii::$app->tuisongbao->getConversations($openId);
        if (!empty($conversations)) {
            $chatTimes = count($conversations);
            $lastChatTime = $conversations[$chatTimes - 1]['lastActiveAt'];
        }

        return [
            'conversationId' => $conversationId,
            'helpdesk' => $originalHelpdesk,
            'client' => $client,
            'targetHelpdesk' => $targetHelpdesk,
            'lastChatTime' => $lastChatTime,
            'chatTimes' => $chatTimes,
            'startTime' => TimeUtil::msTime()
        ];
    }

    /**
     * Update helpdesks and clients last active time
     * @param  array $message  chat or event message contains createdAt field
     */
    private function updateUserActivity($message)
    {
        $accountId = $message['content']['extra']['accountId'];
        $activities = ChatConversation::getActivityMap($accountId);
        $activities[$message['from']] = TimeUtil::string2MsTime($message['createdAt']);
        ChatConversation::setActivityMap($accountId, $activities);
    }

    /**
     * Parse webhook request body and validate signature
     * @return array the webhook request body (parsed from JSON payload)
     */
    private function getWebhookBody()
    {
        $query = $this->getQuery();
        $bodyStr = Yii::$app->request->getRawBody();
        $headers = Yii::$app->request->getHeaders();
        $signature = hash_hmac('sha256', $bodyStr, TUISONGBAO_SECRET);
        $body = [];
        LogUtil::info([
            'message' => 'Got tuisongbao webhook message',
            'bodyStr' => $bodyStr,
            'headers' => ArrayHelper::toArray($headers),
            'signature' => $signature
        ], 'tuisongbao-webhook');
        // Check whether it is called by the tuisongbao web hook
        if ($signature === $headers['X-Engine-Signature']) {
            $body = json_decode($bodyStr, true);
        }
        return $body;
    }
}
