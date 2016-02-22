<?php
namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\utils\LogUtil;
use Yii;

/**
 * This is the webHook model class for aug-marketing
 *
 * The followings are the available columns in collection 'webHook':
 * @property MongoId    $_id
 * @property string     $url
 * @property boolean    $isEnabled
 * @property string     $channels
 * @property MongoId    $accountId
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @author Vincent Hou
 **/
class WebHook extends PlainModel
{

   private $originalChannels;

    /**
     * Declares the name of the Mongo collection associated with webHook.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'webHook';
    }

    /**
     * Returns the list of all attribute names of webHook.
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
            ['url', 'isEnabled', 'channels']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['url', 'isEnabled', 'channels']
        );
    }

    /**
     * Returns the list of all rules of webHooks.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['url', 'isEnabled'], 'required'],
                ['isEnabled', 'default', 'value' => true],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into webHooks.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'url', 'isEnabled', 'channels',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2TimeStamp($this->createdAt);
                }
            ]
        );
    }

    public static function getByAccount($accountId)
    {
        return self::findOne(['accountId' => $accountId]);
    }

    public function beforeSave($insert)
    {
        $this->originalChannels = $this->getAvailableChannels();
        return parent::beforeSave($insert);
    }

    public function beforeDelete()
    {
        $this->originalChannels = $this->getAvailableChannels();
        return parent::beforeDelete();
    }

    public function afterSave($insert, $changedAttributes)
    {
        $this->toggleWebhook();
        return parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete()
    {
        $this->toggleWebhook();
        return parent::afterDelete();
    }

    private function getAvailableChannels()
    {
        $channels = [];
        $webHooks = self::findByCondition([
            'accountId'=> $this->accountId,
            'isEnabled'=> true
        ], false);
        foreach ($webHooks as $webHook) {
            $channels = array_merge($channels, $webHook->channels);
        }
        return array_unique($channels);
    }

    /**
     * Toggle the switch of weconnect based on the enabled webhooks amount
     * @param  boolean $isDelete Weather it is a deleting operation for webhook model
     */
    private function toggleWebhook()
    {
        $action = 'Enable';
        $newChannels = $this->getAvailableChannels();
        $diffChannels = array_diff($this->originalChannels, $newChannels);
        // If add new channels
        if (0 == count($diffChannels)) {
            $diffChannels = array_diff($newChannels, $this->originalChannels);
            foreach ($diffChannels as $channel) {
                Yii::$app->weConnect->enableEventWebhookRules($channel);
            }
        } else {
            $action = 'Disable';
            foreach ($diffChannels as $channel) {
                Yii::$app->weConnect->disableEventWebhookRules($channel);
            }
        }
        if (count($diffChannels) != 0) {
            LogUtil::info([
                'action' => "$action channels",
                'channels' => $diffChannels
            ], 'webhook');
        }
    }
}
