<?php
namespace console\modules\management\controllers;

use backend\models\User;
use backend\models\Account;
use backend\models\SensitiveOperation;
use yii\console\Controller;
use Yii;
use console\utils\AccountUtil;
use console\utils\LogUtil;
use MongoId;

/**
 * Add the default sensitive operation
 **/
class SensitiveOperationController extends Controller
{
    /**
     * Add default sensitive operation for all accounts
     */
    public function actionIndex()
    {
        $accounts = Account::findAll([]);
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $options = Yii::$app->params['sensitive_options'];
                foreach ($options as $name => $options) {
                    SensitiveOperation::initOptions($name, $options, $account->_id);
                }
            }
        }
        echo "Fininsh init the sensitive operation.\n";
    }

    /**
     * Remove sensitive operation by name
     */
    public function actionRemove($name, $accountIds = '')
    {
        $count = 0;
        $accountIds = $this->getAccountIds($accountIds);
        foreach ($accountIds as $accountId) {
            $sensitiveOperations = SensitiveOperation::findByName($name, $accountId);
            foreach ($sensitiveOperations as $sensitiveOperation) {
                $count += $sensitiveOperation->delete();
            }
        }
        LogUtil::info("Deleted $count records");
    }

    /**
     * Hide menus for operators for account
     */
    public function actionHideMenus($module, $menus, $ruleName, $accountIds = '')
    {
        $menus = split(',', $menus);
        $moduleMenus = AccountUtil::getMenusOfModule($module);
        $forbiddenStates = [];
        foreach ($moduleMenus as $moduleName => $moduleItems) {
            foreach ($moduleItems as $menu) {
                if (in_array($menu['name'], $menus)) {
                    if (1 == $menu['order']) {
                        if ('store' == $module) {
                            $msg = 'Store entry can not be changed, it is hard coded in frontend';
                        } else {
                            $msg = 'Please select a new entry for module first and execute "yii management/account/add-menus-and-mods"';
                        }
                        LogUtil::error($msg);
                        return;
                    } else {
                        $forbiddenStates[] = $menu['state'];
                    }
                }
            }
        }
        $accountIds = $this->getAccountIds($accountIds);
        foreach ($accountIds as $accountId) {
            $this->hideMenusForOperators($forbiddenStates, $menus, $ruleName, $accountId);
        }
    }

    private function hideMenusForOperators($forbiddenStates, $menus, $ruleName, $accountId)
    {
        $sensitiveOperation = new SensitiveOperation();
        $sensitiveOperation->name = empty($ruleName) ? "Hide module menus": $ruleName;
        $sensitiveOperation->users = [];
        $sensitiveOperation->states = $forbiddenStates;
        $sensitiveOperation->isActivated = true;
        $sensitiveOperation->accountId = $accountId;
        if ($sensitiveOperation->save()) {
            LogUtil::success("Hide menus for account $accountId successfully");
        } else {
            LogUtil::error("Fail to hide menus for account $accountId");
        }
    }

    private function getAccountIds($accountIds)
    {
        if (empty($accountIds)) {
            $accountIds = array_map(function($account){
                return $account->_id;
            }, Account::findAll([]));
        } else {
            $accountIdStrs = split(',', $accountIds);
            $accountIds = array_map(function($accountIdStr){
                return new MongoId($accountIdStr);
            }, $accountIdStrs);
        }
        return $accountIds;
    }
}
