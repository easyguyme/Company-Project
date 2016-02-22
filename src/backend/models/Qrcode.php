<?php

namespace backend\models;

use Yii;
use backend\utils\MongodbUtil;
use backend\components\BaseModel;
use backend\components\ActiveDataProvider;
use backend\exceptions\InvalidParameterException;
use backend\models\Store;
use backend\models\Staff;
use yii\helpers\ArrayHelper;
use backend\models\Channel;

/**
 * Model class for qrcode.
 *
 * The followings are the available columns in collection 'qrcode':
 * @property MongoId $_id
 * @property String $type
 * @property ObjectId $associatedId
 * @property String $content
 * @property String $qiniuKey
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property ObjectId $accountId
 **/

class Qrcode extends BaseModel
{
    const TYPE_MEMBER = 'member';
    const TYPE_GAME = 'game';

    /**
     * Declares the name of the Mongo collection associated with qrcode.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'qrcode';
    }

    /**
     * Returns the list of all attribute names of member.
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
            ['type', 'associatedId', 'content', 'qiniuKey']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['type', 'associatedId']
        );
    }

    /**
     * Returns the list of all rules of ChatMessage.
     * This method must be overridden by child classes to define available attributes.
     *
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
     * The default implementation returns the names of the columns whose values have been populated into qrcode.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'type',
                'associatedId' => function () {
                    return (string) $this->associatedId;
                }
            ]
        );
    }

    public static function getByTypeAndAssociated($type, $associatedId, $accountId)
    {
        return self::findOne(['type' => $type, 'associatedId' => $associatedId, 'accountId' => $accountId]);
    }

    /**
     * get qrcodeId name:struct array(qrcodeId=>value)
     * @param $qrcodeIds,array
     */
    public static function getQrcodeName($qrcodeIds)
    {
        $data = [];
        //promotion qrcode only check in store and staff
        if (!empty($qrcodeIds)) {
            $where = Store::createStoreChannelCondition($qrcodeIds);
            $storeInfos = Store::findAll($where);
            $storeIds = [];
            if (!empty($storeInfos)) {
                $model = new Store;
                $channels = $model->storeChannels;

                foreach ($storeInfos as $storeInfo) {
                    foreach ($channels as $channel) {
                        if (!empty($channel = $storeInfo->$channel)) {
                            $storeIds[] = $channel['qrcodeId'];
                            $data[$channel['qrcodeId']] = $storeInfo->name;
                        }
                    }
                }
            }
            //get staff qrcode
            $staffIds = array_values(array_diff($qrcodeIds, $storeIds));
            $where = ['qrcodeId' => ['$in' => $staffIds]];
            $staffInfos = Staff::findAll($where);

            if (!empty($staffIds)) {
                foreach ($staffInfos as $staffInfo) {
                    $name = '';
                    if (!empty($staffInfo->name)) {
                        $name = $staffInfo->name;
                    }
                    $data[$staffInfo->qrcodeId] = '店员' . $name . '二维码';
                }
            }
        }
        return $data;
    }

    /**
     * pre-process for export qrcode info
     * note: startDate,endDate,refDate is msec
     */
    public static function preProcessData($condition)
    {
        $channelId = $condition['channelId'];
        $qrcodeId = $condition['qrcodeId'];
        $dateCondition = $condition['condition'];
        $results = Yii::$app->weConnect->getQrcodeTimeSeries($channelId, $qrcodeId, $dateCondition);

        $startDate = $dateCondition['startDate'];
        $endDate = $dateCondition['endDate'];

        $data = [];
        if (empty($results)) {
            for ($begin = $startDate; $begin <= $endDate; $begin += 3600 * 24 * 1000) {
                $data[] = [
                    'refDate' => date('Y-m-d', $begin / 1000),
                    'scan' => 0,
                    'subscribe' => 0,
                ];
            }
        } else {
            for ($begin = $startDate; $begin <= $endDate; $begin += 3600 * 24 * 1000) {
                $refDate = ArrayHelper::getColumn($results, 'refDate');
                if (in_array($begin, $refDate)) {
                    foreach ($results as $value) {
                        if ($value['refDate'] == $begin) {
                            $value['refDate'] = date('Y-m-d', $begin / 1000);
                            $data[] = $value;
                        }
                    }
                } else {
                    $data[] =  [
                        'refDate' => date('Y-m-d', $begin / 1000),
                        'scan' => 0,
                        'subscribe' => 0,
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * @return string
     * @param $channelId, string, channel ID
     */
    public static function getAttentionQrcode($channelId)
    {
        $imageUrl = $qrcodeId = '';
        $channelInfo = Channel::getEnableByChannelId($channelId);

        if (empty($channelInfo['qrcodeId'])) {
            $qrcodeId = Channel::createAttentionQrcode($channelId);
            if (!empty($qrcodeId)) {
                Channel::updateAll(['qrcodeId' => $qrcodeId], ['channelId' => $channelId]);
            }
        } else {
            $qrcodeId = $channelInfo['qrcodeId'];
        }

        if (!empty($qrcodeId)) {
            $qrcodeInfo = Yii::$app->weConnect->getQrcode($channelId, $qrcodeId);
            $imageUrl = $qrcodeInfo['imageUrl'];
        }
        return $imageUrl;
    }
}
