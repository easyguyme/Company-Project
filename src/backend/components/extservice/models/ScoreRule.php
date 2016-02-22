<?php
namespace backend\components\extservice\models;

use Yii;
use backend\modules\member\models\ScoreRule as ModelScoreRule;
use backend\modules\member\models\Member;
use backend\models\Channel;
use yii\web\BadRequestHttpException;

/**
 * ScoreRule for extension
 */
class ScoreRule extends BaseComponent
{
    /**
     * Reward by code
     * @param MongoId $memberId
     * @param string $code
     * @param string $origin
     * @param string $channelId
     * @throws BadRequestHttpException
     * @return boolean true: reward success, false: reward failed because of limit
     */
    public function rewardByCode($memberId, $code, $origin, $channelId = '')
    {
        $member = Member::findByPk($memberId);
        if (empty($member)) {
            throw new BadRequestHttpException(Yii::t('member', 'no_member_find'));
        }
        if (!in_array($origin, Member::$origins)) {
            throw new BadRequestHttpException(Yii::t('common', 'invalid_origin'));
        }

        $channelInfo = [
            'id' => '',
            'origin' => $origin,
            'name' => '',
        ];
        if (in_array($origin, [Member::WECHAT, Member::WEIBO, Member::ALIPAY])) {
            if (empty($channelId)) {
                throw new BadRequestHttpException(Yii::t('common', 'invalid_channel_id'));
            }
            $channel = Channel::getByChannelId($channelId, $this->accountId);
            if (empty($channel)) {
                throw new BadRequestHttpException(Yii::t('common', 'invalid_channel_id'));
            }
            if ($origin !== $channel->origin) {
                throw new BadRequestHttpException(Yii::t('common', 'invalid_origin'));
            }
            $channelInfo['id'] = $channelId;
            $channelInfo['name'] = $channel->name;
        }
        return ModelScoreRule::rewardByCode($this->accountId, $member, $code, $channelInfo);
    }
}
