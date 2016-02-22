<?php
/**
 * Controller behavior class file
 * Contains common functions for controllers
 * @author Devin Jin
 **/
 namespace backend\behaviors;

 use MongoId;
 use yii\base\Behavior;
 use yii\web\ForbiddenHttpException;
 use yii\web\UnauthorizedHttpException;
 use backend\components\BaseControllerTrait;
 use backend\models\Token;
 use backend\models\User;
 use backend\models\SensitiveOperation;
 use backend\utils\MongodbUtil;
 use backend\utils\LogUtil;
 use backend\utils\LanguageUtil;
 use Yii;
use backend\models\Account;

class ControllerBehavior extends Behavior
{
    use BaseControllerTrait;

    public function checkAuth($module, $token)
    {
        $baseId = Yii::$app->id;
        $moduleId = $module->id;

        //init i18n configuration from user agent
        Yii::$app->language = LanguageUtil::getBrowserLanguage();

        if ($baseId === $moduleId) {
            return true;
        }

        //accountId
        $accountId = $this->getAccountIdFromCookies();
        if (!empty($accountId) && $this->validateSignature()) {
            return true;
        }

        if (!empty($token)) {
            $info = Token::getToken($token);

            if (!empty($info)) {
                //set the language for i18n
                Yii::$app->language = empty($info->language) ? LanguageUtil::DEFAULT_LANGUAGE : $info->language;

                // If $module is a child module, use the parent module
                if (!empty($module->module->id) && ($module->module->id !== $baseId)) {
                    $module = $module->module;
                    $moduleId = $module->id;
                }

                if (isset($info->expireTime) && !MongodbUtil::isExpired($info->expireTime)) {
                    if (isset($module->roleAccess) && !empty($roleAccess = $module->roleAccess) &&
                        in_array($info->role, $roleAccess) && in_array($moduleId, $info->enabledMods)) {
                        //set the current user
                        $userId = $this->getUserId();
                        $controllerId = $this->owner->id;
                        $actionId = $this->owner->action->id;
                        // the current route
                        // change 'POST product/products' to 'product/product/create'
                        $route = "$moduleId/$controllerId/$actionId";
                        // find the sensitive operation with route
                        $condition = ['isActivated' => true, 'actions' => $route, 'accountId' => $info->accountId];
                        $option = SensitiveOperation::findOne($condition);
                        if (!empty($option)) {
                            // admin has all sensitive operation access authority
                            if ($info->role !== User::ROLE_ADMIN) {
                                if ($info->role !== User::ROLE_OPERATOR) {
                                    // other's role hasn't sensitive operation access authority
                                    throw new ForbiddenHttpException(Yii::t('common', 'no_permission'));
                                } else if (empty($option->users) || !in_array($info->userId, $option->users)) {
                                    throw new ForbiddenHttpException(Yii::t('common', 'no_permission'));
                                }
                            }
                        }
                        define('CURRENT_USER_ID', $userId);
                        $info->expireTime = new \MongoDate(time() + Token::EXPIRE_TIME);
                        $updateResult = $info->update();
                        $this->updateAccessTokenExpire();
                        LogUtil::info(['tokenId' => $info->_id, 'updateResult' => $updateResult]);
                        return true;
                    } else {
                        throw new ForbiddenHttpException(Yii::t('common', 'no_permission'));
                    }
                } else {
                    Yii::$app->language = LanguageUtil::getBrowserLanguage();
                    throw new UnauthorizedHttpException(Yii::t('common', 'login_timeout'));
                }
            }
        }

        throw new UnauthorizedHttpException(Yii::t('common', 'not_logined'));
    }
}
