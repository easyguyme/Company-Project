<?php
namespace backend\components\extservice\models;

use Yii;

/**
 * Url for extension
 * @author Rex Chen
 */
class Url extends BaseComponent
{
    /**
     * Get member personal url
     * @param $memberId string
     * @param $redirectUrl string
     * @return string
     * @author Rex Chen
     */
    public function memberPersonal($memberId, $redirectUrl = '')
    {
        $result = DOMAIN . 'mobile/member/personal?memberId=' . $memberId;
        if (!empty($redirectUrl)) {
            $result .= '&redirect=' . urlencode($redirectUrl);
        }
        return $result;
    }

    /**
     * Get openId by channelId for wechat`s users.
     * @param $channelId string
     * @param $redirectUrl string
     * @return string
     * @author Rex Chen
     */
    public function baseOAuth($channelId, $redirect)
    {
        return DOMAIN . 'api/mobile/base-oauth?channelId=' . $channelId . '&redirect=' . urlencode($redirect);
    }

    /**
     * Get member bind url.
     * @param $channelId string
     * @param $redirectUrl string
     * @return string
     * @author Rex Chen
     */
    public function memberBind($channelId, $redirectUrl = '')
    {
        $result = DOMAIN . 'api/mobile/member?channelId=' . $channelId;
        if (!empty($redirectUrl)) {
            $result .= '&redirect=' . urlencode($redirectUrl);
        }
        return $result;
    }
}
