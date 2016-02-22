<?php
namespace backend\behaviors;

use Yii;
use yii\base\Behavior;
use backend\utils\LanguageUtil;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use backend\models\Account;
use backend\modules\member\models\Member;
use backend\models\User;
use backend\models\Token;
use backend\models\Channel;
use backend\models\Captcha;
use backend\utils\MongodbUtil;

class CaptchaBehavior extends Behavior
{
    /**
     * Get accountId and company when bind
     * @param array $params
     * @throws BadRequestHttpException
     * @throws InvalidParameterException
     * @return array
     */
    public function bind($params)
    {
        \Yii::$app->language = LanguageUtil::LANGUAGE_ZH;
        $this->checkCode($params);
        $mobile = $params['mobile'];
        if (empty($params['channelId']) || empty($params['openId'])) {
            throw new BadRequestHttpException('Missing params');
        }
        $channelId = $params['channelId'];
        $channel = Channel::getEnableByChannelId($channelId);

        $account = Account::findByPk($channel->accountId);
        return ['accountId' => $channel->accountId, 'company' => empty($account->company) ? null : $account->company];
    }

    /**
     * Get accountId and company when signup
     * @param array $params
     * @throws InvalidParameterException
     * @return array
     */
    public function signup($params)
    {
        \Yii::$app->language = LanguageUtil::LANGUAGE_ZH;
        $this->checkCode($params);
        $mobile = $params['mobile'];
        $account = Account::getByPhone($mobile);
        if (!empty($account)) {
            throw new InvalidParameterException(['phone' => \Yii::t('common', 'phone_has_used')]);
        }

        return ['accountId' => null, 'company' => null];
    }

    /**
     * Get accountId and company when update company info
     * @param array $params
     * @throws InvalidParameterException
     * @return array
     */
    public function updateCompanyInfo($params)
    {
        $token = Token::getToken();
        \Yii::$app->language = empty($token->language) ? LanguageUtil::DEFAULT_LANGUAGE : $token->language;
        $this->checkCode($params);
        $mobile = $params['mobile'];
        $accountId = $params['accountId'];
        $account = Account::findByPk($accountId);
        $accountValidate = Account::getByPhone($mobile);
        if (!empty($accountValidate) && $accountValidate->_id . '' != $account->_id . '') {
            throw new InvalidParameterException(['phone' => \Yii::t('common', 'phone_has_used')]);
        } else if (!empty($accountValidate) && $accountValidate->_id . '' == $account->_id . '') {
            throw new InvalidParameterException(['phone' => \Yii::t('management', 'update_same_phone')]);
        }

        return ['accountId' => $accountId, 'company' => null];
    }

    /**
     * Get accountId and company when exchange
     * @param array $params
     * @return array
     */
    public function exchange($params)
    {
        $token = Token::getToken();
        \Yii::$app->language = empty($token->language) ? LanguageUtil::DEFAULT_LANGUAGE : $token->language;
        $this->checkCode($params);
        $accountId = $params['accountId'];
        $account = Account::findByPk($accountId);

        return ['accountId' => $accountId, 'company' => empty($account->company) ? null : $account->company];
    }

    /**
     * Check img captcha code
     * @param array $params
     * @throws InvalidParameterException
     */
    public function checkCode($params)
    {
        $cache = \Yii::$app->cache;
        $code = $cache->get($params['codeId']);
        if (empty($code) || $code !== strtolower($params['code'])) {
            throw new InvalidParameterException(['verification' => \Yii::t('common', 'code_error')]);
        } else {
            $cache->delete($params['codeId']);
        }
    }

    /**
     * Check message captch
     * @param string $mobile
     * @param string $code
     * @throws InvalidParameterException
     */
    public function checkCaptcha($mobile, $code)
    {
        $now = time();
        //get available captcha by mobile
        $captcha = Captcha::getByMobile($mobile);
        if (!empty($captcha)) {
            $sendTimeInt = MongodbUtil::MongoDate2TimeStamp($captcha->createdAt);
            $availabTime = $sendTimeInt + Yii::$app->params['captcha_availab_time'];
            if ($captcha['code'] != $code) {
                throw new InvalidParameterException(['captcha' => Yii::t('common', 'captcha_error')]);
            }
            $captcha->isExpired = true;
            $captcha->save(true, ['isExpired']);
            if ($now > $availabTime) {
                throw new InvalidParameterException(['captcha' => Yii::t('common', 'captcha_expired')]);
            }
        } else {
            throw new InvalidParameterException(['phone' => Yii::t('common', 'mobile_error')]);
        }
    }
}
