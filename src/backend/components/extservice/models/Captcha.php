<?php
namespace backend\components\extservice\models;

use backend\models\Captcha as ModelCaptcha;

/**
 * Captcha for extension
 */
class Captcha extends BaseComponent
{
    /**
     * Record captcha
     * @param string $mobile
     * @param string $code
     * @param string $userIp
     * @return boolean
     */
    public function record($mobile, $code, $userIp)
    {
        $captcha = new ModelCaptcha();
        $captcha->ip = $userIp;
        $captcha->code = $code;
        $captcha->mobile = $mobile;
        $captcha->isExpired = false;
        $captcha->accountId = $this->accountId;
        return $captcha->save();
    }

    /**
     * Get latest captcha by mobile
     * @param string $mobile
     */
    public function getLastestByMobile($mobile)
    {
        $condition = ['mobile' => $mobile, 'accountId' => $this->accountId];
        return ModelCaptcha::find()->where($condition)->orderBy(['createdAt' => SORT_DESC])->one();
    }
}
