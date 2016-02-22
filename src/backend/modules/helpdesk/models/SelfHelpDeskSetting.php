<?php
namespace backend\modules\helpdesk\models;

use Yii;
use backend\components\PlainModel;
use yii\web\BadRequestHttpException;
use backend\modules\helpdesk\models\ChatConversation;
use backend\modules\helpdesk\models\ChatMessage;
use backend\utils\TimeUtil;
use backend\utils\LogUtil;

/**
 * Model class for selfHelpDeskSetting.
 *
 * The followings are the available columns in collection 'selfHelpDeskSetting':
 * @property MongoId   $_id
 * @property Object    $settings
 * @property MongoId   $accountId
 * @property MongoDate $createdAt
 **/
class SelfHelpDeskSetting extends PlainModel
{
    const TYPE_BACK = 'back';
    const TYPE_CONNECTDESK = 'connect';
    const TYPE_REPLY = 'reply';
    const CONVERSATION_PREFIX = 'conversation-';
    const CONVERSATION_MODE_PREFIX = 'conversation-mode-';
    const EXPIRED_TIME = 3600;

    /**
     * Declares the name of the Mongo collection as associated with selfHelpDeskSetting.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'selfHelpDeskSetting';
    }

    /**
     * Returns the list of all attribute names of selfHelpDeskSetting.
     * This method must be overriden by child classesto define avaliable attributes.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            //['keyword', 'type', 'content', 'parent']
            ['settings']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            //['keyword', 'type', 'content', 'parent']
            ['settings']
        );
    }

    /**
     * Returns the list of all rules of user.
     * This method must be overriden by child classes to define avaliable attributes.
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into selfHelpDeskSetting.
     * @return array the fields.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['settings']
        );
    }

    public static function exists($accountId)
    {
        $where = ['accountId' => new \MongoId($accountId)];
        $selfSetting = self::findOne($where);
        return !empty($selfSetting);
    }

    public static function getReply($accountId, $env = '0')
    {
        $envs = explode(',', $env);
        $where = ['accountId' => new \MongoId($accountId)];
        $selfSetting = self::findOne($where);
        $reply = $selfSetting['settings'];
        if (empty($reply)) {
            return null;
        }
        switch (count($envs)) {
            case 0:
            case 1:
                return $reply;
                break;
            case 2:
                if (array_key_exists($envs[1], $reply['menus'])) {
                    return $reply['menus'][$envs[1]];
                }
                break;
            case 3:
                if (array_key_exists($envs[1], $reply['menus'])) {
                    if (array_key_exists($envs[2], $reply['menus'][$envs[1]]['menus'])) {
                        return $reply['menus'][$envs[1]]['menus'][$envs[2]];
                    }
                }
                break;
            default:
                break;
        }
        return null;
    }

    public static function autoReply($args)
    {
        LogUtil::info(['SelfHelpDesk args' => $args], 'selfhelpdesk');
        $message = $args['message'];
        $type = $message['msgType'];
        $content = $message['content'];
        $WEConnectAccountInfo = $args['account'];
        $WEConnectUserInfo = $args['user'];
        $accountId = new \MongoId($args['accountId']);

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

        $client = [
            'nick' => $WEConnectUserInfo['nickname'],
            'avatar' => $WEConnectUserInfo['headerImgUrl'],
            'openId' => $WEConnectUserInfo['originId'],
            'channelId' => $WEConnectUserInfo['accountId'],
            'source' =>  $source,
            'accountId' => $accountId,
            'channelInfo' => [
                'type' => $accountInfoType,
                'name' => $WEConnectAccountInfo['name']
            ]
        ];

        $redis = \Yii::$app->cache->redis;
        $key = self::CONVERSATION_PREFIX . $accountId . '-' . $client['openId'];
        $modeKey = self::CONVERSATION_MODE_PREFIX . $accountId . '-' . $client['openId'];

        if ('EVENT' === $type) {
            // DISCONNECT
            if ('DISCONNECT' == $content) {
                $redis->del($key);
                $redis->del($modeKey);
                HelpDesk::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_BRAKE);
                return;
            }
            // CONNECT
            $settings = self::getReply($accountId);
            $redis->set($key, '0', 'EX', self::EXPIRED_TIME);
        } else {
            $env = $redis->get($key);
            if (empty($env)) {
                $env = '0';
            }
            $settings = self::getReply($accountId, $env . ',' . $content);
            if (!empty($settings)) {
                switch ($settings['type']) {
                    case self::TYPE_BACK:
                        $envs = explode(',', $env);
                        $size = count($envs);
                        if ($size > 1) {
                            $env = '';
                            for ($i = 0; $i < $size - 1; $i++) {
                                $env = $env . $envs[$i] . ',';
                            }
                            $env = trim($env, ',');
                            $redis->set($key, $env, 'EX', self::EXPIRED_TIME);
                            $settings = self::getReply($accountId, $env);
                        }
                        break;
                    case self::TYPE_CONNECTDESK:
                        $isInWorkingHour = HelpDeskSetting::isInWorkingHours($accountId);
                        if ($isInWorkingHour) {
                            $redis->del($key);
                            $redis->set($modeKey, 'false', 'EX', self::EXPIRED_TIME);
                            Yii::$app->job->create('backend\modules\chat\job\WechatMessage', $args);
                        } else {
                            //send the disconnect event to WeConnect
                            HelpDesk::sendSystemReplyByType($client, $accountId, HelpDeskSetting::REPLY_NONWORKING);
                            Yii::$app->weConnect->sendCustomerServiceMessage($client['openId'], $client['channelId'], ['msgType' => ChatConversation::WECHAT_MESSAGE_TYPE_EVENT, 'content' => 'DISCONNECT']);
                        }
                        return ['status' => 'ok'];
                        break;
                    case self::TYPE_REPLY:
                        $envs = explode(',', $env);
                        $size = count($envs);
                        if ($size < 2) {
                            $env = $env . ',' . $content;
                            $redis->set($key, $env, 'EX', self::EXPIRED_TIME);
                        }
                        break;
                    default:
                        break;
                }
            } else {
                $settings = self::getReply($accountId, $env);
            }
        }

        if (!empty($settings['content'])) {
            $message = [
                'msgType' => ChatMessage::MSG_TYPE_TEXT,
                'content' => $settings['content'],
                'createTime' => TimeUtil::msTime()
            ];
            LogUtil::info(['SelfHelpDesk reply message' => $message], 'selfhelpdesk');
            Yii::$app->weConnect->sendCustomerServiceMessage($client['openId'], $client['channelId'], $message);
        }
    }
}
