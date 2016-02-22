<?php
namespace backend\modules\helpdesk\models;

use Yii;
use yii\web\ServerErrorHttpException;
use backend\components\BaseModel;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\modules\helpdesk\models\HelpDesk;
use MongoId;
use MongoDate;
use backend\components\ActiveDataProvider;
use backend\modules\chat\traits\OpenMessageTrait;
use backend\modules\helpdesk\models\ChatSession;

/**
 * Model class for ChatConversation.
 *
 * This is a special model to wrap all the data of tuisongbao service
 **/
class ChatConversation
{
    use OpenMessageTrait;

    //constants for status
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    //constants for webhook
    const NEW_MESSAGE_EVENT = 'message_new';

    //constants for global channel
    const CHANNEL_GLOBAL = 'presence-wm-global';
    const EVENT_FORCED_OFFLINE = 'force_offline';
    const EVENT_USER_ADDED = 'user_added';
    const EVENT_USER_REMOVED = 'user_removed';

    //constants for cache prefix (prefix + accountId)
    const CONVERSATIONS_KEY = 'conversations';
    const ACTIVITIES_KEY = 'activities';

    //constants for types
    const TYPE_WEBSITE = 'website';
    const TYPE_WECHAT = 'wechat';
    const TYPE_WEIBO = 'weibo';
    const TYPE_ALIPAY = 'alipay';
    const TYPE_IOS = 'ios';
    const TYPE_ANDROID = 'android';

    //contants for wechat message types
    const WECHAT_MESSAGE_TYPE_EVENT = 'EVENT';

    //constants for event names
    const LEAVE_ACTION = 'leave';
    const TRANSFER_ACTION = 'transfer';
    const CHAT_ACTION = 'chat';
    const JOIN_ACTION = 'join';

    //push message
    const PUSH_MESSAGE_NEW_CLIENT = '有新的客户接入';
    const PUSH_MESSAGE_TRANSFER = '客服{desk}转接了一个客户';

    //constants for unread count prefix
    const UNREAD_COUNT_PREFIX = 'helpdesk-unread';
    const MONGOID_LENGTH = 24;
    const WEBUSER_LENGTH = 37;

    //constants for tuisongbao event names

    //constants for system_reply
    const NO_DUPLICATE_CLIENT = '您已开启了一个会话，请勿重复开启';

    //latest session pages
    const SESSION_PAGES = 1;

    public static function getConversationMap($accountId)
    {
        return Yii::$app->cache->get(self::CONVERSATIONS_KEY . $accountId);
    }

    public static function setConversationMap($accountId, $data)
    {
        return Yii::$app->cache->set(self::CONVERSATIONS_KEY . $accountId, $data);
    }

    public static function getActivityMap($accountId)
    {
        return Yii::$app->cache->get(self::ACTIVITIES_KEY . $accountId);
    }

    public static function setActivityMap($accountId, $data)
    {
        return Yii::$app->cache->set(self::ACTIVITIES_KEY . $accountId, $data);
    }

    public static function getConversationId($openId, $deskId)
    {
        $conversations = Yii::$app->tuisongbao->getConversations($openId, $deskId);
        if (!empty($conversations) && is_array($conversations)) {
            return $conversations[0]['conversationId'];
        }
    }

    /**
     * Remove the helpdesk or client in related cache
     * @param  string $id               id of helpdesk or client
     * @param  string $accountId        account id
     * @param  boolean $isHelpdesk      whether the user is helpdesk
     * @param  string $clientHelpdeskId the helpdesk id if remove client
     */
    public static function removeUser($id, $accountId, $isHelpdesk = true, $clientHelpdeskId = '')
    {
        $conversations = self::getConversationMap($accountId);
        $ids = [$id];
        //remove it from conversation caches
        if ($isHelpdesk) {
            $activeConversations = Yii::$app->tuisongbao->getConversations($id);
            $severdClientIds = empty($conversations[$id]) ? [] : $conversations[$id];
            foreach ($activeConversations as $activeConversation) {
                $conversationId = $activeConversation['conversationId'];
                $client = $activeConversation['extra']['client'];
                if (!empty($client['openId'])) {
                    $clientId = $client['openId'];
                    if (in_array($clientId, $severdClientIds)) {
                        // Client is online
                        Yii::$app->tuisongbao->notifyClientLeft($client, $id, $conversationId, $accountId, true);
                    } else {
                        // Client is offline
                        Yii::$app->tuisongbao->removeConversation($id, $clientId);
                    }
                }
            }

            if (!empty($conversations[$id])) {
                $ids = array_merge($ids, $conversations[$id]);
            }
            unset($conversations[$id]);
            //flush the client count in db
            HelpDesk::flushClientCount(new MongoId($id));
        } else {
            $conversations = self::getConversationMap($accountId);
            if (empty($clientHelpdeskId)) {
                $findOne = false;
                foreach ($conversations as $helpdeskId => $clientIds) {
                    foreach ($clientIds as $idx => $clientId) {
                        if ($id == $clientId) {
                            unset($conversations[$helpdeskId][$idx]);
                            HelpDesk::decClientCount(new MongoId($helpdeskId));
                            break;
                        }
                    }
                    if ($findOne) {
                        break;
                    }
                }
            } else {
                $clientIds = $conversations[$clientHelpdeskId];
                foreach ($clientIds as $idx => $clientId) {
                    if ($id == $clientId) {
                        unset($conversations[$clientHelpdeskId][$idx]);
                        HelpDesk::decClientCount(new MongoId($clientHelpdeskId));
                        break;
                    }
                }
            }
        }
        self::setConversationMap($accountId, $conversations);

        //remove the helpdesk activities
        $activities = self::getActivityMap($accountId);
        foreach ($ids as $value) {
            unset($activities[$value]);
        }
        self::setActivityMap($accountId, $activities);
    }

    public static function zeroActiveTime($userId, $accountId)
    {
        $activities = self::getActivityMap($accountId);
        if (isset($activities[$userId])) {
            $activities[$userId] = 0;
        }
        self::setActivityMap($accountId, $activities);
    }

    /**
     * Check if the client is served by a helpdesk
     * @param  string $id        open id of client
     * @param  string $accountId account id
     * @return array             mapping data for helpdesk and client
     */
    public static function checkUserServed($id, $accountId)
    {
        $isServed = false;
        $data = [];
        $conversations = ChatConversation::getConversationMap($accountId);
        foreach ($conversations as $helpdesk => $userIds) {
            foreach ($userIds as $userId) {
                if ($userId == $id) {
                    $data['originalDeskId'] = $helpdesk;
                    $data['openId'] = $id;
                    $isServed = true;
                    break;
                }
            }
            if ($isServed) {
                break;
            }
        }
        return $data;
    }
}
