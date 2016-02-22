<?php
namespace backend\behaviors;

use yii\base\Behavior;
use backend\models\Account;
use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\models\WebHook;
use backend\models\Channel;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

class ChannelBehavior extends Behavior
{
    /**
     * Synchroniz account channels, remove disable channels
     * @param Account $account
     * @throws ServerErrorHttpException
     * @return array
     */
    public function syncAccountChannels($accountId)
    {
        $channelIds = Channel::getEnableChannelIds($accountId);

        $channelInfo = ['wechat' => [], 'weibo' => [], 'alipay' => []];
        if (empty($channelIds)) {
            return $channelInfo;
        }

        $weChannels = \Yii::$app->weConnect->getAccounts($channelIds);
        $this->syncWebHookChannels($accountId, $weChannels);

        $enbaleChannel = [];
        foreach ($weChannels as $weChannel) {
            if ($weChannel['channel'] == Account::WECONNECT_CHANNEL_WEIXIN && !empty($weChannel['refreshToken'])) {
                $channelInfo['wechat'][] = $weChannel;
                $enbaleChannel[] = $weChannel['id'];
            } else if ($weChannel['channel'] == Account::WECONNECT_CHANNEL_WEIXIN && !empty($weChannel['appSecret'])) {
                $channelInfo['wechat'][] = $weChannel;
                $enbaleChannel[] = $weChannel['id'];
            } else if ($weChannel['channel'] == Account::WECONNECT_CHANNEL_WEIBO) {
                $weChannel['appkey'] = WEIBO_APP_KEY;
                $channelInfo['weibo'][] = $weChannel;
                $enbaleChannel[] = $weChannel['id'];
            } else if ($weChannel['channel'] == Account::WECONNECT_CHANNEL_ALIPAY) {
                $channelInfo['alipay'][] = $weChannel;
                $enbaleChannel[] = $weChannel['id'];
            }
            Channel::updateAll(
                ['$set' => ['name' => $weChannel['name'], 'type' => empty($weChannel['accountType']) ? '' : $weChannel['accountType']]],
                ['channelId' => $weChannel['id'], 'accountId' => $accountId]
            );
        }

        $disableChannelIds = array_diff($channelIds, $enbaleChannel);
        if (!empty($disableChannelIds)) {
            $disableChannelIds = array_values($disableChannelIds);
            Channel::disableByChannelIds($accountId, $disableChannelIds);
        }

        return $channelInfo;
    }

    /**
     * Synchroniz help-desk-settings channels, remove disable channels
     * @param HelpDeskSetting $setting
     * @throws ServerErrorHttpException
     * @return HelpDeskSetting
     */
    public function syncHelpdeskChannels()
    {
        $setting = $this->owner;
        if (empty($setting['channels'])) {
            return $setting;
        } else {
            $channels = $setting['channels'];
        }
        //get enable channels
        $channelIds = [];
        foreach ($channels as $channel) {
            $channelIds[] = $channel['id'];
        }
        $weChannels = \Yii::$app->weConnect->getAccounts($channelIds);
        $weChannelIds = [];
        foreach ($weChannels as $weChannel) {
            $weChannelIds[] = $weChannel['id'];
        }

        //unset disable channel
        $channelInfos = [];
        foreach ($channels as $key => $channel) {
            if (in_array($channel['id'], $weChannelIds)) {
                $channelInfos[] = $channel;
            }
        }

        //if channels has changed, save new channels
        if (count($channelInfos) != count($setting['channels'])) {
            $setting->channels = $channelInfos;
            if (!$setting->save(true, ['channels'])) {
                throw new ServerErrorHttpException(\Yii::t('channel', 'data_synchronization_failed'));
            }
        }

        return $setting;
    }

    public function syncWebHookChannels($accountId, $weChannels)
    {
        $webHook = WebHook::getByAccount($accountId);
        if (empty($webHook) || empty($webHook->channels)) {
            return true;
        }

        $wechannelIds = ArrayHelper::getColumn($weChannels, 'id');
        $enableChannels = array_intersect($webHook->channels, $wechannelIds);
        if (count($enableChannels) === count($webHook->channels)) {
            return true;
        }

        $webHook->channels = $enableChannels;
        if (!$webHook->save(true, ['channels'])) {
            throw new ServerErrorHttpException(\Yii::t('channel', 'data_synchronization_failed'));
        }
        return true;
    }
}
