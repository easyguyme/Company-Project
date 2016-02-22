<?php

namespace backend\modules\chat\traits;

use backend\models\Token;
use backend\utils\MongodbUtil;
use backend\utils\LanguageUtil;
use backend\utils\LogUtil;
use backend\modules\helpdesk\models\HelpDesk;
use Yii;
use backend\components\BaseControllerTrait;

/**
 * This is a trait for chat module controllers
 * @author Harry Sun
 */
trait ControllerTrait
{
    use BaseControllerTrait;
    /**
     * This method is used to valide the user's authority with token in help desk chat system.
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (parent::beforeAction($action)) {
     *         // your custom code here
     *         return true;  // or false if needed
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to run.
     * @author Harry Sun
     */
    public function beforeAction($action)
    {
        $route = $this->id . '/' . $action->id;
        //init i18n configuration from user agent
        Yii::$app->language = LanguageUtil::getBrowserLanguage();
        // the action ids without auth
        $noAuth = [
            'site/login', 'site/logout', 'client/online', 'setting/index', 'setting/self-helpdesk',
            'site/send-reset-password-email', 'site/reset-password', 'help-desk/check-auth', 'conversation/user-presence',
            'conversation/message-webhook', 'issue/create-from-js-sdk', 'issue/remove-attachment', 'graphic/view',
            'help-desk/login', 'help-desk/get-user-info'
        ];

        if (in_array($route, $noAuth)) {
            return true;
        } else {
            $accessToken = $this->getQuery('accesstoken');
            $info = Token::getToken($accessToken);
            if (!empty($info) && isset($info->expireTime) && !MongodbUtil::isExpired($info->expireTime)) {
                Yii::$app->language = empty($info->language) ? LanguageUtil::DEFAULT_LANGUAGE : $info->language;
                $expireTime = new \MongoDate(time() + Token::EXPIRE_TIME);
                if ($info->expireTime < $expireTime) {
                    $info->expireTime = $expireTime;
                }
                $updateResult = $info->update();
                return true;
            }

            LogUtil::error(['accessToken' => $accessToken, 'message' => 'You have not logined']);
            throw new \yii\web\UnauthorizedHttpException('You have not logined');
        }
    }

    public function getUser()
    {
        $userId = $this->getUserId();
        return HelpDesk::findOne(['_id' => $userId]);
    }

    /**
     * Get accesstoken from cookies
     * @return string
     * @author Rex Chen
     */
    public function getAccessToken()
    {
        return $this->getQuery('accesstoken');
    }
}
