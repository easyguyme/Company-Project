<?php
namespace backend\modules\helpdesk\models;

use Yii;
use MongoId;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\components\BaseModel;
use backend\utils\StringUtil;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\models\PendingClient;
use backend\modules\member\models\Member;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\modules\helpdesk\models\SelfHelpDeskSetting;
use backend\modules\helpdesk\models\ChatConversation;

/**
 * Model class for account.
 *
 * The followings are the available columns in collection 'account':
 * @property MongoId   $_id
 * @property string    $name
 * @property string    $badge
 * @property string    $email
 * @property string    $password
 * @property string    $salt
 * @property string    $avatar
 * @property string    $language
 * @property boolean   $isEnabled
 * @property boolean   $isActivated
 * @property int       $clientCount
 * @property string    $notificationType
 * @property MongoDate $lastLoginAt
 * @property boolean   $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property ObjectId  $accountId
 * @author Devin.Jin
 **/
class HelpDesk extends BaseModel
{
    const BROWSER = 'browser';
    const MOBILEAPP = 'mobile_app';

    //environment
    const ENVIRONMENT_IOS_PRO = 'ap';
    const ENVIRONMENT_IOS_DEV = 'ad';
    const ENVIRONMENT_ANDROID = 'tps';

    const NOTIFICATION_TYPE_DESKTOP_MARK = 'desktop-mark';
    const NOTIFICATION_TYPE_MARK = 'mark';

     // the status of customer.
    const EVENT_ONLINE_STATUS = 'online_status_change';

    /**
     * Declares the name of the Mongo collection associated with user.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'helpDesk';
    }

    /**
     * Returns the list of all attribute names of user.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'createdAt', 'updatedAt', 'isDeleted'];
     * }
     * ```
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['badge', 'email', 'avatar', 'isEnabled', 'isActivated', 'salt', 'password', 'notificationType', 'lastLoginAt', 'name', 'language', 'clientCount', 'loginDevice', 'deviceToken', 'environment', 'tags', 'kfAccount']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['badge', 'email', 'avatar', 'isEnabled', 'isActivated', 'salt', 'password', 'notificationType', 'lastLoginAt', 'name', 'language', 'clientCount', 'loginDevice', 'deviceToken', 'environment', 'tags', 'kfAccount']
        );
    }

    /**
     * Returns the list of all rules of user.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['isEnabled', 'default', 'value' => true],
                ['isActivated', 'default', 'value' => false],
                ['language', 'default', 'value' => 'zh_cn'],
                ['avatar', 'default', 'value' => Yii::$app->params['defaultAvatar']],
                ['clientCount', 'default', 'value' => new \MongoInt32(0)],
                ['loginDevice', 'default', 'value' => self::BROWSER],
                ['environment', 'validateEnvironment'],
                ['notificationType', 'default', 'value' => self::NOTIFICATION_TYPE_DESKTOP_MARK],
                ['notificationType', 'in', 'range' => [self::NOTIFICATION_TYPE_DESKTOP_MARK, self::NOTIFICATION_TYPE_MARK]]
            ]
        );
    }

    public function validateEnvironment($attribute)
    {
        if ($attribute != 'environment') {
            return true;
        }

        $environments = [self::ENVIRONMENT_IOS_PRO, self::ENVIRONMENT_IOS_DEV, self::ENVIRONMENT_ANDROID];
        if (!in_array($this->$attribute, $environments)) {
            throw new BadRequestHttpException('Invalid environment');
        }

        return true;
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'badge', 'email', 'avatar', 'isEnabled', 'isActivated', 'name', 'language', 'clientCount', 'loginDevice', 'tags', 'notificationType', 'kfAccount',
                'lastLoginAt' => function ($model) {
                    return empty($model['lastLoginAt']) ? '' : MongodbUtil::MongoDate2msTimeStamp($model['lastLoginAt']);
                },
                'isOnline' => function ($model) {
                    $conversations = ChatConversation::getConversationMap($model->accountId);

                    if (is_array($conversations) && array_key_exists($model->_id . '', $conversations)) {
                        return true;
                    }

                    return false;
                },
                'conversationCount' => function ($model) {
                    $conversations = ChatConversation::getConversationMap($model->accountId);

                    if (is_array($conversations) && !empty($conversations)) {
                        if (isset($conversations[$model->_id . ''])) {
                            return count($conversations[$model->_id . '']);
                        }
                    }

                    return 0;
                },
                'maxClient' => function ($model) {
                    $helpDeskSetting = HelpDeskSetting::getInstance($model->accountId);
                    if (empty($helpDeskSetting['maxClient'])) {
                        throw new ServerErrorHttpException('maxClient error');
                    }
                    return $helpDeskSetting['maxClient'];
                }
            ]
        );
    }

    /**
     * Find helpDesk by email
     * @param string, $email
     * @return Array, HelpDesk info
     */
    public static function getByEmail($email)
    {
        return HelpDesk::findOne(['email' => $email]);
    }

    /**
     * flush the client count
     * @param ObjectId, $id, the PK
     */
    public static function flushClientCount($id)
    {
        self::updateAll(['clientCount' => 0], ['_id' => $id]);
    }

    /**
     * make the client count inc by 1
     * @param  ObjectId $id the PK
     */
    public static function incClientCount($id, $maxClientLimit)
    {
        return self::updateAll(['$inc' => ['clientCount' => 1]], ['_id' => $id, 'clientCount' => ['$lt' => $maxClientLimit]]);
    }

    /**
     * make the client count dec by 1
     * @param  ObjectId $id the PK
     */
    public static function decClientCount($id)
    {
        self::updateAll(['$inc' => ['clientCount' => -1]], ['_id' => $id, 'clientCount' => ['$gt' => 0]]);
    }

    /**
     * Find helpDesk by badge
     * @param string, $badge
     * @return Array, HelpDesk info
     */
    public static function getByBadge($badge, $accountId)
    {
        return HelpDesk::findOne(['badge' => $badge, 'accountId' => $accountId]);
    }

    /**
     * Validate the password
     * @param  string $password help desk password
     * @return boolean
     */
    public function validatePassword($password)
    {
        $password = self::encryptPassword($password, $this->salt);
        return $password == $this->password;
    }

    /**
     * Encrypt the password when save user.
     *
     * Using md5 to encrypt the password when save user.
     *
     * @param string $password The origin password that user input
     * @param string $salt The generate salt
     * @author Devin Jin
     * @return string The password that after md5
     */
    public static function encryptPassword($password, $salt = 'abcdef')
    {
        return md5(md5(trim($password)) . $salt);
    }

    /**
     * Get helpdesk by name
     * @param String $name
     * @return array
     **/
    public static function getByName($accountId, $name)
    {
        return HelpDesk::findOne(['accountId' => $accountId, 'name' => $name]);
    }

    /**
     * Get helpdesk by id
     * @param  MongoId $helpdeskId  helpdesk id
     */
    public static function getById($helpdeskId)
    {
        return HelpDesk::findOne(['_id' => $helpdeskId]);
    }

    /**
     * Get helpdesk list by tag name
     * @param String $tagName
     * @return array
     **/
    public static function getByAccountAndTags($tags, $accountId, $orderBy = 'createdAt')
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $orderBy = self::normalizeOrderBy(['orderBy' => $orderBy]);

        $condition = ['tags' => ['$in' => $tags], 'accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        return HelpDesk::find()->where($condition)->orderBy($orderBy)->all();
    }

    /**
     * Get helpdesk list exculde tag name
     * @param String $tagName
     * @return array
     **/
    public static function getExculdeTags($tags, $accountId)
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $condition = ['tags' => ['$nin' => $tags], 'accountId' => $accountId, 'isDeleted' => self::NOT_DELETED];
        return HelpDesk::find()->where($condition)->all();
    }

    /**
     * Add new tag to helpdesk
     * @param String $tagName
     * @param String $helpdeskId
     * @return array
     **/
    public static function addTag($tagName, $helpdeskIds)
    {
        self::updateAll(['$addToSet' => ['tags' => $tagName]], ['_id' => ['$in' => $helpdeskIds]]);
    }

    /**
     * Remove one tag from helpdesk
     * @param String $tagName
     * @param String $helpdeskId
     * @return array
     **/
    public static function removeTag($tagName, $helpdeskIds)
    {
        self::updateAll(['$pull' => ['tags' => $tagName]], ['_id' => ['$in' => $helpdeskIds]]);
    }

    /**
     * Check the connect client whether has the VIP helpdesk
     * @param array $client
     * @param MongoId $accountId
     * @return mixed
     */
    public static function hasVIPDesk($client, $accountId)
    {
        // Check the client from 'website' or 'WeConnect'
        if (!empty($client['openId'])) {
            $member = Member::getByOpenId($client['openId']);
            if (!empty($member) && !empty($member->tags)) {
                // Find member's tags
                $client['tags'] = $member->tags;
                $helpdesks = self::getByAccountAndTags($member->tags, $accountId);
                if (!empty($helpdesks)) {
                    $maxClient = HelpDeskSetting::getMaxClientCount($accountId);
                    // Find all online and can server helpdesk
                    $availableDeskIds = [];
                    foreach ($helpdesks as $helpdesk) {
                        $helpdesk = $helpdesk->toArray();
                        if ($helpdesk['isOnline'] && $helpdesk['clientCount'] < $maxClient) {
                            $availableDeskIds[] = new MongoId($helpdesk['id']);
                        }
                    }

                    if (!empty($availableDeskIds)) {
                        // Find lastest server helpdesk
                        $conversations = Yii::$app->tuisongbao->getConversations($client['openId']);
                        foreach ($conversations as $conversation) {
                            if (in_array($conversation['target'], $availableDeskIds)) {
                                if (!empty($conversation['lastMessage'])) {
                                    return $conversition['target'];
                                }
                            }
                        }
                        return (string)$availableDeskIds[0];
                    }
                }
            }
        }
        return false;
    }

    /**
     * Try to connect to a helpDesk after a client joined
     * Online available helpdesks will be assigned to new client.
     * New client will be pushed to the pending client queue for waiting if no available helpdesks
     * @param  array $client the client information, containing openId, nick, avatar
     * @param  MongoId $accountId the account UUID
     * @return array ['status': 'fail'] or ['status': 'ok', 'helpDesk':'...', 'channel':'....', 'coversationId':'...']
     */
    public static function connect($client, $accountId)
    {
        LogUtil::info([
            'message' => 'Connect helpdesk for client',
            'client' => $client,
            'accountId' => $accountId
        ], 'chat');
        if ($targetDeskId = self::hasVIPDesk($client, $accountId)) {
            LogUtil::info(['message' => 'Connect VIP helpdesk'], 'chat');
            return self::createConnection($client, $targetDeskId, $accountId);
        } else if ($targetDeskId = self::getLastestDesk($client['openId'], $accountId)) {
            LogUtil::info(['message' => 'Connect last chatted helpdesk'], 'chat');
            return self::createConnection($client, $targetDeskId, $accountId);
        } else {
            $conversations = ChatConversation::getConversationMap($accountId);
            if ($conversations) {
                //Find a online helpdesk
                LogUtil::info(['message' => 'Connect online helpdesk'], 'chat');
                $maxClientsCount = HelpDeskSetting::getMaxClientCount($accountId);
                $targetDeskId = null;
                foreach ($conversations as $helpdeskId => &$clientList) {
                    $currentCount = count($clientList);
                    if ($currentCount < $maxClientsCount) {
                        $maxClientsCount = $currentCount;
                        $targetDeskId = $helpdeskId;
                    }
                }

                if (empty($targetDeskId)) {
                    //push the client into pending clients's queue
                    LogUtil::info(['message' => 'Put client into pending list if no available helpdesk'], 'chat');
                    PendingClient::enQueue($client);
                    self::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_WAITTING);
                    return ['status' => 'failed', 'client' => $client];
                } else {
                    //Find one helpdesk
                    LogUtil::info(['message' => 'Find any available helpdesk'], 'chat');
                    return self::createConnection($client, $targetDeskId, $accountId);
                }
            } else {
                LogUtil::info(['message' => 'Put client into pending list if no helpdesk'], 'chat');
                //push the client into pending clients's queue
                PendingClient::enQueue($client);
                self::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_WAITTING);
                return ['status' => 'failed', 'client' => $client];
            }
        }
    }

    /**
     * Get avaliable helpdesk list
     * @param  MongoId $accountId
     * @return array  the deskIds that can serve for client
     * @author Mike Wang
     */
    public static function getAvaliableHelpdesks($accountId)
    {
        $conversations = ChatConversation::getConversationMap($accountId);
        $maxClientLimit = HelpDeskSetting::getMaxClientCount($accountId);
        $availableDeskIds = [];
        if (!empty($conversations)) {
            foreach ($conversations as $helpdeskId => &$clientList) {
                $currentCount = count($clientList);
                if ($currentCount < $maxClientLimit) {
                    array_push($availableDeskIds, $helpdeskId);
                }
            }
        }
        return $availableDeskIds;
    }

    /**
     * Get the last helpdeskId with client openId.
     * @param  array $client the client information, containing openId, nick, avatar
     * @param  ObjectId $accountId The account id
     * @param  string $excludeHelpdeskId The ids that exclude from conversation
     * @return  string $helpdeskId the helpdesk UUID
     * @author  Mike Wang
     */
    public static function getLastestDesk($openId, $accountId, $excludeHelpdeskId = '')
    {
        $availableDeskIds = self::getAvaliableHelpdesks($accountId);
        $lastDeskId = '';
        //Suggestion: May need ask tuisongbao provide a API to get last chat message
        $conversations = Yii::$app->tuisongbao->getConversations($openId, '', '', '', false);
        if (!empty($conversations)) {
            foreach ($conversations as $conversation) {
                if (in_array($conversation['target'], $availableDeskIds)) {
                    if (!empty($conversation['lastMessage']) && $conversation['target'] != $excludeHelpdeskId) {
                        return $conversation['target'];
                    }
                }
            }
        }
        LogUtil::info(['message' => 'Get latest helpdesk', 'lastDeskId' => $lastDeskId], 'chat');
        return $lastDeskId;
    }

    /**
     * Initialize the conversation between helpdesk and client under an account.
     * Update redis cache and store the chat conversations in mongodb
     * @param  array $client the client information, containing openId, nick, avatar
     * @param  string $helpdeskId the helpdesk UUID
     * @param  MongoId $accountId the account UUID
     * @return array response for pushing clientJoined state and connect event for wechat
     */
    public static function createConnection($client, $helpdeskId, $accountId)
    {
        $openId = $client['openId'];
        //create chatConversation in tuisongbao
        $conversationId = Yii::$app->tuisongbao->createConversation($client, $helpdeskId);
        $data = ['client' => $client, 'helpdeskId' => $helpdeskId, 'conversationId' => $conversationId];

        if (!empty($conversationId)) {
            $maxClientLimit = HelpDeskSetting::getMaxClientCount($accountId);
            $inLimited = HelpDesk::incClientCount(new MongoId($helpdeskId), $maxClientLimit);
            if (!$inLimited) {
                //push the client into pending clients's queue
                LogUtil::info(['message' => 'Put client into pending list if no available helpdesk'], 'chat');
                PendingClient::enQueue($client);
                self::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_WAITTING);
                return ['status' => 'failed', 'client' => $client];
            }

            //update the conversation information in cache
            $conversations = ChatConversation::getConversationMap($accountId);
            $conversations[$helpdeskId][] = $openId;
            ChatConversation::setConversationMap($accountId, $conversations);

            LogUtil::info(['message' => 'Create conversation successfully', 'data' => $data], 'chat');
            //notify client join
            $client['accountId'] = (string) $accountId;
            Yii::$app->tuisongbao->notifyClientJoined($client, $helpdeskId, $conversationId, $accountId);
            //send message to weconnect client user
            if (!empty($client['channelId'])) {
                self::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_SUCCESS);
            }

        } else {
            LogUtil::error(['message' => 'Fail to create conversation for tuisongbao', 'data' => $data], 'chat');
        }

        return $data;
    }

    /**
     * Get the clients in pending
     * @param string $helpdeskId
     * @param MongoId $accountId
     * @return integer $count
     */
    public static function connectPendingClient($helpdeskId, $accountId, $count = 1)
    {
        $clients = PendingClient::deQueue($helpdeskId, $count);

        foreach ($clients as $client) {
            self::createConnection($client, $helpdeskId, $accountId);
        }
    }

    /**
     * Helpdesk join to the system
     * @param string $helpdeskId
     * @param MongoId $accountId
     * @return array users that the helpdesk serve
     */
    public static function join($helpdeskId, $accountId, $time = null)
    {
        $cache = Yii::$app->cache;
        //add the help desk to the cache
        $conversations = ChatConversation::getConversationMap($accountId);
        //in case the helpDesk is already online
        if (empty($conversations[$helpdeskId])) {
            $conversations[$helpdeskId] = [];
            ChatConversation::setConversationMap($accountId, $conversations);
            //get the client from pending queue
            $maxClientLimit = HelpDeskSetting::getMaxClientCount($accountId);
            HelpDesk::connectPendingClient($helpdeskId, $accountId, $maxClientLimit);
            LogUtil::info(['message' => 'Helpdesk online at first time', 'helpdeskId' => $helpdeskId], 'chat');
        }
        //update activity map
        $activities = ChatConversation::getActivityMap($accountId);
        $time = empty($time) ? TimeUtil::msTime() : $time;
        $activities[$helpdeskId] = $time;
        ChatConversation::setActivityMap($accountId, $activities);

        return $conversations[$helpdeskId];
    }

    /**
     * Send reply message to WeConnect user by type
     * @param array $client
     * @param string $accountId
     * @param string $type
     */
    public static function sendSystemReplyByType($client, $accountId, $type, $content = null)
    {
        $helpDeskSetting = HelpDeskSetting::getInstance($accountId);
        $defaultSystemReplies = $helpDeskSetting->systemReplies;

        if ($type !== HelpDeskSetting::REPLY_CUSTOM) {
            foreach ($defaultSystemReplies as $defaultSystemReply) {
                if ($defaultSystemReply['type'] === $type) {
                    $content = $defaultSystemReply['isEnabled'] ? $defaultSystemReply['replyText'] : '';
                }
            }
        }

        if (!empty($content)) {
            $message = [
                'msgType' => ChatMessage::MSG_TYPE_TEXT,
                'content' => $content,
                'createTime' => TimeUtil::msTime()
            ];
            if (!empty($client['channelId'])) {
                Yii::$app->weConnect->sendCustomerServiceMessage($client['openId'], $client['channelId'], $message);
            }
        } else if ($content === null) {
            throw new ServerErrorHttpException('Incorrect name for default helpdesk system reply');
        }
    }

    /**
     * Destroy the conversation between helpdesk and client
     * @param  string $conversationId the conversation UUID
     * @param  MongoId $accountId the account UUID
     * @param  array $extra extra information got from request
     * @return array success status for conversation disconnection
     */
    public static function weconnectClientLeave($client, $helpdeskId, $conversationId, $extra = null)
    {
        $accountId = $client['accountId'];
        //trigger the client left event
        Yii::$app->tuisongbao->notifyClientLeft($client, $helpdeskId, $conversationId, $accountId);

        //send the desk left message to client for wechat, weibo or alipay
        LogUtil::info(['message' => 'welcome the leaving for client', 'client' => $client, 'accountId' => $accountId, 'extra' => $extra], 'chat');
        self::notifyWeonnectDisconnent($accountId, $client, $helpdeskId, $extra['type']);
    }

    public static function notifyWeonnectDisconnent($accountId, $client, $helpdeskId, $type)
    {
        self::sendSystemReplyByType($client, $accountId, $type);
        //send the disconnect event to WeConnect
        Yii::$app->weConnect->sendCustomerServiceMessage($client['openId'], $client['channelId'], ['msgType' => ChatConversation::WECHAT_MESSAGE_TYPE_EVENT, 'content' => 'DISCONNECT']);
        //remove conversation from client list
        Yii::$app->tuisongbao->removeConversation($client['openId'], $helpdeskId);
        //change to self-helpdesk mode
        $modeKey = SelfHelpDeskSetting::CONVERSATION_MODE_PREFIX . $accountId . '-' . $client['openId'];
        Yii::$app->cache->redis->del($modeKey);
    }

    /**
     * Remove a client form cache and db
     * @param  MongoId $helpdeskId  helpdesk id
     * @param  string $openId  client open id
     * @param  array $clients  original client list
     * @return array the updated client list
     */
    public function removeClient($helpdeskId, $openId, $clients)
    {
        $findOne = false;
        foreach ($clients as $index => $clientId) {
            if ($clientId == $openId) {
                unset($clients[$index]);
                $findOne = true;
                break;
            }
        }
        if ($findOne) {
            HelpDesk::decClientCount($helpdeskId);
        }
        return $clients;
    }
}
