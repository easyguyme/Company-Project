<?php
namespace backend\models;

use backend\components\PlainModel;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;
use Exception;
use yii\web\BadRequestHttpException;

/**
 * Model class for channel.
 *
 * The followings are the available columns in collection 'channel':
 * @property MongoId $_id
 * @property string, $channelId
 * @property string, $origin
 * @property string, $name
 * @property string, $type
 * @property string, $status
 * @property boolean, $isTest
 * @property MongoId, accountId
 * @property MongoDate $createdAt
 **/
class Channel extends PlainModel
{
    //constants for status
    const STATUS_ENABLE = 'enable';
    const STATUS_DISABLE = 'disable';

    const ATTENTION_QRCODE = 'subscribe_qrcode';
    /**
     * Declares the name of the Mongo collection associated with channel.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'channel';
    }

    /**
     * Returns the list of all attribute names of channel.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['channelId', 'appId', 'origin', 'name', 'type', 'qrcodeId', 'status', 'isTest']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['channelId', 'appId', 'origin', 'name', 'type', 'qrcodeId', 'status', 'isTest']
        );
    }

    /**
     * Returns the list of all rules of channel.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['status', 'in', 'range' => [self::STATUS_DISABLE, self::STATUS_ENABLE]],
                ['origin', 'in', 'range' => [self::ALIPAY, self::WECHAT, self::WEIBO]]
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into channel.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'channelId', 'origin', 'name', 'type', 'status', 'isTest'
            ]
        );
    }

    public static function getByAccountAndChannelId($accountId, $channelId)
    {
        return self::findOne(['accountId' => $accountId, 'channelId' => $channelId]);
    }

    public static function getEnableByAccount($accountId)
    {
        return self::findAll(['accountId' => $accountId, 'status' => self::STATUS_ENABLE]);
    }

    public static function getAllByAccount($accountId)
    {
        return self::findAll(['accountId' => $accountId]);
    }

    public static function disableByChannelIds($accountId, $channelIds)
    {
        return self::updateAll(
            ['$set' => ['status' => self::STATUS_DISABLE]],
            ['accountId' => $accountId, 'channelId' => ['$in' => $channelIds]]
        );
    }

    public static function getEnableCountByAccountId($accountId)
    {
        return self::count(['accountId' => $accountId, 'status' => self::STATUS_ENABLE]);
    }

    public static function getEnableChannelIds($accountId)
    {
        $channels = self::getEnableByAccount($accountId);
        return ArrayHelper::getColumn($channels, 'channelId');
    }

    public static function getEnableByChannelId($channelId)
    {
        return self::findOne(['channelId' => $channelId, 'status' => self::STATUS_ENABLE]);
    }

    /**
     * Get channel info by channelId
     * @param MongoId $channelId
     * @param MongoId $accountId
     * @return \yii\db\static
     * @author Rex Chen
     */
    public static function getByChannelId($channelId, $accountId = null)
    {
        $condition = ['channelId' => $channelId];
        if (!empty($accountId)) {
            $condition['accountId'] = $accountId;
        }
        //if accountId is empty, get enable channel first
        return self::find()->where($condition)->orderBy(['status' => SORT_DESC])->one();
    }

    public static function getWechatByAccount($accountId, $isTest = null)
    {
        $condition = ['accountId' => $accountId, 'origin' => self::WECHAT, 'status' => self::STATUS_ENABLE];
        if ($isTest !== null) {
            $condition['isTest'] = $isTest;
        }
        $wechatChannels = self::findAll($condition);
        return ArrayHelper::getColumn($wechatChannels, 'channelId');
    }

    public static function getWeiboByAccount($accountId)
    {
        $condition = ['accountId' => $accountId, 'origin' => self::WEIBO, 'status' => self::STATUS_ENABLE];
        $wechatChannels = self::findAll($condition);
        return ArrayHelper::getColumn($wechatChannels, 'channelId');
    }

    public static function upsert($accountId, $channelId, $origin, $name, $type, $isTest, $appId = '')
    {
        $channel = self::getByAccountAndChannelId($accountId, $channelId);
        if (empty($channel)) {
            $channel = new Channel();
            $channel->accountId = $accountId;
            $channel->channelId = $channelId;
        }
        $channel->origin = $origin;
        $channel->name = $name;
        $channel->type = $type;
        $channel->isTest = $isTest;
        $channel->status = self::STATUS_ENABLE;

        $channel->appId = (string)$appId;
        $channel->qrcodeId = !empty($channel->qrcodeId) ? $channel->qrcodeId : '';

        return $channel->save();
    }

    /**
     * create a attention qrcode
     * @return string. qrcode id
     */
    public static function createAttentionQrcode($channelId)
    {
        $channelInfo = Channel::getByChannelId($channelId);

        if (empty($channelInfo)) {
            return '';
        }

        if (empty($channelInfo['qrcodeId'])) {
            //defined a special name for qrcodeName,this param must be pass
            $specialStr = md5($channelInfo->accountId . $channelId);
            $qrcodeName = Channel::ATTENTION_QRCODE . '_' . $specialStr;
            $qrcode = [
                'type' => 'CHANNEL',
                'name' => $qrcodeName,
            ];

            switch ($channelInfo['origin']) {
                case self::ALIPAY:
                    $weConnectChannelInfo = Yii::$app->weConnect->getAccount($channelId);
                    if (strtolower($weConnectChannelInfo['accessStatus']) != 'success') {
                        return '';
                    }
                    break;
            }

            try {
                $qrcodeInfo = Yii::$app->weConnect->createQrcode($channelId, $qrcode);
                if (!empty($qrcodeInfo)) {
                    return $qrcodeInfo['id'];
                } else {
                    return '';
                }
            } catch (Exception $e) {
                return '';
            }
        } else {
            return $channelInfo['qrcodeId'];
        }
    }

    /**
     * Get all origins by accountId
     * @param MongoId $accountId
     * @return array
     * @author Rex Chen
     */
    public static function getOriginsByAccount($accountId)
    {
        $origins = self::distinct('origin', ['accountId' => $accountId]);
        return $origins ? $origins : [];
    }
}
