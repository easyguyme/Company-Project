<?php
namespace backend\models;

use Yii;
use backend\components\BaseModel;
use backend\utils\StringUtil;
use backend\utils\MongoDate2String;
use backend\utils\MongodbUtil;
use backend\models\MessageTemplate;
use backend\exceptions\InvalidParameterException;

/**
 * Model class for staff.
 * The followings are the available columns in collection 'staff':
 * @property MongoId $_id
 * @property MongoId $storeId
 * @property string $phone
 * @property string $badge
 * @property string $name
 * @property string $gender
 * @property int $birthday
 * @property array $channel {channelId, channelType, channelName}
 * @property string $status
 * @property boolean $isEnabled
 * @property string $qrcodeUrl
 * @property string $qrcodeId
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property MongoId $accountId
 **/

class Staff extends BaseModel
{
    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';

    const CHANNEL_SUBSCRIBE_MESSAGE = '欢迎光临，店员 - %店员名字%为您服务。';

    /**
    * Declares the name of the Mongo collection associated with staff.
    * @return string the collection name
    */
    public static function collectionName()
    {
        return 'staff';
    }

    /**
    * Returns the list of all attribute names of staff.
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
            ['storeId', 'phone', 'badge', 'name', 'gender','birthday', 'channel', 'status', 'isActivated', 'qrcodeUrl', 'qrcodeId', 'qrcodeName', 'salt']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['storeId', 'phone', 'badge', 'name', 'gender','birthday', 'channel', 'isActivated', 'qrcodeUrl', 'qrcodeId', 'qrcodeName', 'salt']
        );
    }
    /**
    * Returns the list of all rules of staff.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['isActivated', 'default', 'value' => false],
                ['status', 'default', 'value' => self::STATUS_OFFLINE],
                ['gender', 'default', 'value' => ''],
                ['birthday', 'default', 'value' => ''],
                ['name', 'default', 'value' => ''],
                ['qrcodeUrl', 'default', 'value' => ''],
                ['storeId', 'toMongoId'],
            ]
        );
    }

    public static function checkPhone($storeId, $phone)
    {
        $where = ['storeId' => $storeId, 'phone' => $phone];
        $result = self::findOne($where);
        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    public static function checkUnique($badge, $accountId)
    {
        $staff = self::findOne(['badge' => $badge, 'accountId' => $accountId]);
        if (empty($staff)) {
            return false;
        } else {
            return true;
        }
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into Product.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'storeId' => function ($model) {
                    return (string)$model->storeId;
                },
                'phone', 'badge', 'name', 'gender', 'birthday','channel','qrcodeUrl',
                'isActivated' => function ($model) {
                    $status = $model->isActivated ? 'ENABLE' : 'DISABLE';
                    return $status;
                },
                'accountId' => function ($model) {
                    return (string) $model->accountId;
                },
                'createdAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->createdAt, 'Y-m-d H:i:s');
                },
            ]
        );
    }

    /**
     * get the mobile template to notice the staff
     */
    public static function getMobileTemplate($accountId)
    {
        $where = ['accountId' => $accountId, 'name' => MessageTemplate::STAFF_TITLE];
        $template = MessageTemplate::findOne($where);
        return $template['mobile']['message'];
    }

    /**
     * set the qrcode imgurl and qrcodeId
     */
    public static function setQrcodeParam($channelId)
    {
        $uuid = StringUtil::uuid();
        $qrcodeData = [
            'name' => $uuid,
            'type' => 'CHANNEL',
            'temporary' => false,
        ];
        $qrcode = Yii::$app->weConnect->createQrcode($channelId, $qrcodeData);
        $params = [
            'qrcodeUrl' => $qrcode['imageUrl'],
            'qrcodeId' => $qrcode['id'],
            'qrcodeName' => $uuid,
        ];
        return $params;
    }

    /**
     * Get the total, onSaleTotal and offSaleTotal
     * @return array
     */
    public static function getTotal($storeId, $accountId)
    {
        $condition = ['status' => self::STATUS_ONLINE, 'storeId' => $storeId, 'accountId' => $accountId];
        $onlineTotal = self::count($condition);
        $condition['status'] = self::STATUS_OFFLINE;
        $offlineTotal = self::count($condition);
        return [
            'total' => $onlineTotal + $offlineTotal,
            'onlineTotal' => $onlineTotal,
            'offlineTotal' => $offlineTotal
        ];
    }

    /**
     * override conver2MongoId in parent method
     */
    public static function conver2MongoId()
    {
        return ['storeId'];
    }

    /**
     * define a message to user when user subscribe the platform
     * @param $staff,object
     * @param $username,string
     */
    public static function setQrcodeMessage($staff, $username)
    {
        $channelId = $staff->channel['channelId'];
        $message = str_replace('%店员名字%', $username, self::CHANNEL_SUBSCRIBE_MESSAGE);
        $qrcodeId = $staff->qrcodeId;

        //get  qrcode name
        if (!empty($staff->qrcodeName)) {
            $qrcodeName = $staff->qrcodeName;
        } else {
            $qrcodeInfo = Yii::$app->weConnect->getQrcode($channelId, $qrcodeId);
            if (empty($qrcodeInfo['name'])) {
                throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
            }
            $qrcodeName = $qrcodeInfo['name'];
        }

        $qrcode = [
            'msgType' => 'TEXT',
            'content' => $message,
            'name' => $qrcodeName,
        ];
        Yii::$app->weConnect->updateQrcode($channelId, $qrcodeId, $qrcode);
    }
}
