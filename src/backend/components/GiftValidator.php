<?php
namespace backend\components;

use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;

class GiftValidator
{
    const TYPE_GIFT_LOTTERY = 'lottery';
    const TYPE_GIFT_SCORE = 'score';

    const METHOD_LOTTERY_SCALE = 'scale';
    const METHOD_LOTTERY_NUMBER = 'number';

    const METHOD_SCORE_TIMES = 'times';
    const METHOD_SCORE_SCORE = 'score';

    public static function validateGift($gift)
    {
        if (empty($gift['type'])) {
            throw new InvalidParameterException(['promotionGiftType' => \Yii::t('product', 'required_gift_type')]);
        }
        if (empty($gift['config'])) {
            throw new BadRequestHttpException('missing gift config');
        }

        switch ($gift['type'])
        {
            case self::TYPE_GIFT_LOTTERY:
                $config = $gift['config'];
                if (empty($config['method']) || !in_array($config['method'], [self::METHOD_LOTTERY_SCALE, self::METHOD_LOTTERY_NUMBER])) {
                    throw new InvalidParameterException(['giftLotteryType' => \Yii::t('product', 'invalide_gift_config_type')]);
                }
                if (!is_array($config['prize'])) {
                    throw new BadRequestHttpException('prize must be an array');
                } else {
                    $index = 0;
                    foreach ($config['prize'] as &$prize) {
                        if (empty($prize['name'])) {
                            throw new BadRequestHttpException('missing param gift.config.prize.name');
                        }
                        if (!array_key_exists('number', $prize)) {
                            throw new InvalidParameterException('missing param gift.config.prize.number');
                        }
                        if ($config['method'] == self::METHOD_LOTTERY_SCALE && (!is_numeric($prize['number']) || $prize['number'] < 0)) {
                            throw new InvalidParameterException(['prizeNumber' . $index => \Yii::t('product', 'invalid_prize_scale')]);
                        } else if ($config['method'] == self::METHOD_LOTTERY_SCALE) {
                            $prize['number'] = sprintf("%.2f", $prize['number']);
                        }

                        if ($config['method'] == self::METHOD_LOTTERY_NUMBER && (!is_int($prize['number']) || $prize['number'] < 0)) {
                            throw new InvalidParameterException(['prizeNumber' . $index => \Yii::t('product', 'invalid_prize_number')]);
                        }
                        $index++;
                    }
                }
                $gift['config'] = $config;
                break;
            case self::TYPE_GIFT_SCORE:
                $config = $gift['config'];
                if (empty($config['method']) || !in_array($config['method'], [self::METHOD_SCORE_SCORE, self::METHOD_SCORE_TIMES])) {
                    throw new InvalidParameterException(['giftScoreType' => \Yii::t('product', 'invalide_gift_config_type')]);
                }
                if ($config['method'] == self::METHOD_SCORE_TIMES && (!is_int($config['number']) || $config['number'] < 1)) {
                    throw new InvalidParameterException(['giftRewardTimes' => \Yii::t('product', 'invalide_gift_times_number')]);
                }
                if ($config['method'] == self::METHOD_SCORE_SCORE && (!is_int($config['number']) || $config['number'] < 1)) {
                    throw new InvalidParameterException(['giftRewardScore' => \Yii::t('product', 'invalide_gift_score_number')]);
                }
                break;
            default:
                throw new InvalidParameterException(['promotionGiftType' => \Yii::t('product', 'invalide_gift_type')]);
                break;
        }

        return $gift;
    }
}
