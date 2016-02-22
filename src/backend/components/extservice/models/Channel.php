<?php
namespace backend\components\extservice\models;

use backend\models\Channel as ModelChannel;

/**
 * Channel for extension
 * @author Rex Chen
 */
class Channel extends BaseComponent
{
    /**
     * Get enable channel by channelId
     * @return array
     */
    public function enableOne($channelId)
    {
        return ModelChannel::getEnableByChannelId($channelId);
    }

    /**
     * Get by channelId
     * @param string $channelId
     * @return \yii\db\static
     * @author Rex Chen
     */
    public function getByChannelId($channelId)
    {
        return ModelChannel::getByChannelId($channelId, $this->accountId);
    }

    /**
     * Get channel by channelId, if the $one is true,this api only return a array,otherwise it will return multi arrays
     * @param $channelId, array, channel id array
     * @param $one, boolean,
     * @return array
     */
    public function getById($channelId, $one = true)
    {
        if ($one) {
            return ModelChannel::findOne(['channelId' => ['$in' => $channelId]]);
        } else {
            return ModelChannel::findAll(['channelId' => ['$in' => $channelId]]);
        }
    }
}
