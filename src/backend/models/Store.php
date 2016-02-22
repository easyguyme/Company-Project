<?php
namespace backend\models;

use Yii;
use backend\Utils\LogUtil;
use backend\components\BaseModel;

/**
 * This is the admin model class for aug-marketing
 *
 * The followings are the available columns in collection 'user':
 * @property MongoId    $_id
 * @property string     $name
 * @property string     $branchName
 * @property string     $type
 * @property string     $subtype
 * @property string     $telephone
 * @property array      $location
 * @property string     $position
 * @property string     $image
 * @property string     $businessHours
 * @property string     $description
 * @property array      $wechat
 * @property array      $weibo
 * @property boolean    $isDeleted
 * @property MongoDate  $createdAt
 * @property MongoDate  $updatedAt
 * @property MongoId    $accountId
 * @author Harry Sun
 **/
class Store extends BaseModel
{
    const CACHE_PREFIX = 'store';
    const CACHE_EXPIRE_TIME = 300;
    const SYNC_FROM_WECHAT = 'sync';
    const SYNC_TO_WECHAT = 'push';

    //set the channel for save the qrcode
    public $storeChannels = [self::WECHAT, self::WEIBO, self::ALIPAY];
    /**
     * Declares the name of the Mongo collection associated with store.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'store';
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
            ['name', 'branchName', 'type', 'subtype', 'telephone', 'location', 'position', 'image', 'businessHours', 'description', 'wechat', 'weibo', 'alipay']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'branchName', 'type', 'subtype', 'telephone', 'location', 'position', 'image', 'businessHours', 'description', 'wechat', 'weibo', 'alipay']
        );
    }

    /**
     * Returns the list of all rules of admin.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                // the name, email, password and salt attributes are required
                //[['name', 'email', 'password', 'salt'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name', 'branchName', 'type', 'subtype', 'telephone', 'location', 'position', 'image', 'businessHours', 'description', 'wechat', 'weibo', 'alipay']
        );
    }

    public function toData()
    {
        $storeData = [];
        $storeData['business_name'] = $this->name;
        $storeData['branch_name'] = $this->branchName;
        $storeData['province'] = isset($this->location['province']) ? $this->location['province'] : '';
        $storeData['city'] =  isset($this->location['city']) ? $this->location['city'] : '';
        $storeData['district'] =  isset($this->location['district']) ? $this->location['district'] : '';
        $storeData['address'] =  isset($this->location['detail']) ? $this->location['detail'] : '';
        $storeData['telephone'] = $this->telephone;
        $storeData['category'] = $this->type;
        $storeData['longitude'] = isset($this->position['longitude']) ? $this->position['longitude'] : '';
        $storeData['latitude'] = isset($this->position['latitude']) ? $this->position['latitude'] : '';
        return $storeData;
    }

    public function loadData($storeData)
    {
        $this->name = $storeData['name'];
        $this->location = [
            'province' => isset($storeData['province']) ? $storeData['province'] : '',
            'city' => isset($storeData['city']) ? $storeData['city'] : '',
            'district' => isset($storeData['district']) ? $storeData['district'] : '',
            'detail' => $storeData['address']
        ];
        $this->telephone = $storeData['phone'];
        $this->position = ['latitude' => $storeData['latitude'], 'longitude' => $storeData['longitude']];
    }

    /**
     * Combine the location
     * @return string
     */
    public function getStoreLocation()
    {
        $location = isset($this->location['province']) ? $this->location['province'] : '';
        $location .=  isset($this->location['city']) ? $this->location['city'] : '';
        $location .=  isset($this->location['district']) ? $this->location['district'] : '';
        $location .=  isset($this->location['detail']) ? $this->location['detail'] : '';
        return $location;
    }

    /**
     * get store info base on the qrcodeId
     * @param $qrcodeId,string
     */
    public static function getStoreByQrcodeId($qrcodeId)
    {
        $condition = self::createStoreChannelCondition($qrcodeId);
        $store = Store::findOne($condition);
    }

    /**
     * create condition about store channel
     * @param $qrcodeId, string or array
     */
    public static function createStoreChannelCondition($qrcodeId)
    {
        $model = new Store;
        $channels = $model->storeChannels;
        $condition = [];
        if (is_array($qrcodeId)) {
            foreach ($channels as $channel) {
                $condition[] = [$channel . '.qrcodeId' => ['$in' => $qrcodeId]];
            }
        } else {
            foreach ($channels as $channel) {
                $condition[] = [$channel . '.qrcodeId' => $qrcodeId];
            }
        }
        return ['$or' => $condition];
    }

     /**
     * delete store data
     * @param $channelId,string
     */
    public static function deleteStoreQrcode($channelId, $qrcodeId)
    {
        $channelInfo = Channel::getEnableByChannelId($channelId);
        if (empty($channelInfo->origin)) {
            throw new InvalidParameterException('invaild channelId');
        }
        $field = $channelInfo->origin;
        $where = [$field . '.qrcodeId' => $qrcodeId];
        Store::updateAll([$field => null], $where);
    }

    /**
     * delete all qrcode info about store
     * @param $store. object
     */
    public static function deleteStoreAllQrcode($store)
    {
        $model = new Store;
        $channels = $model->storeChannels;

        foreach ($channels as $channel) {
            if (!empty($channel = $store->$channel)) {
                $channelId = $channel['channelId'];
                $id = $channel['qrcodeId'];
                try {
                    Yii::$app->weConnect->deleteQrcode($channelId, $id);
                } catch (\Exception $e) {
                    LogUtil::info(['message' => 'Faild to delete qrcode when you delete store', 'error' => $e->getMessage(), 'channelId' => $channelId, 'qrcodeId' => $id], 'store');
                }
            }
        }
    }
}
