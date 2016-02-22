<?php
namespace console\modules\management\controllers;

use yii\console\Controller;
use backend\models\Account;
use yii\helpers\Json;
use Yii;
use console\utils\AccountUtil;

/**
 * Scan the modules folder to get the public extensions
 **/
class ModuleController extends Controller
{
    /**
     * get module config from backend(get-config)
     */
    public function actionGetConfig()
    {
        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        $filenames = scandir($modulesDIR);

        $moduleNames = [];
        foreach ($filenames as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_file($modulesPath . DIRECTORY_SEPARATOR . $filename . '/Module.php')) {
                $moduleNames[] = $filename;
            }
        }

        echo Json::encode(Yii::$app->extModule->getMenuAndExtName($moduleNames));
    }

    public function actionInstall($modules, $accountIds = [])
    {
        $condition = [];
        if (!empty($accountIds)) {
            $ids = explode(',', $accountIds);
            $MSG = 'Update modules for ';
            AccountUtil::hint($MSG);
            AccountUtil::info(json_encode($ids));
            $accountIds = AccountUtil::getMongoIds($ids);
            unset($ids);
        } else {
            $accountIds = AccountUtil::getAllAccount();
            $MSG = 'Update module for all account.';
            AccountUtil::warning($MSG);
        }
        if (!empty($modules)) {
            $mods = explode(',', $modules);
        } else {
            $MSG = 'invalid parameters, modules must be required.';
            AccountUtil::error($MSG);
            return;
        }
        $moduleNames = AccountUtil::getModules($mods);
        while (!empty(array_diff($moduleNames, $mods))) {
            $mods = $moduleNames;
            $moduleNames = AccountUtil::getModules($mods);
        }
        AccountUtil::info(json_encode($moduleNames));
        $MSG = 'These modules will be update:';
        AccountUtil::hint($MSG);
        AccountUtil::info(json_encode($moduleNames));
        $results = Yii::$app->extModule->getMergedConfig($moduleNames);
        $MSG = 'All message show as follow:';
        AccountUtil::hint($MSG);
        AccountUtil::info(json_encode($results));
        AccountUtil::updateMods($accountIds, $results['mods']);
        AccountUtil::updateMenus($accountIds, $results['menus']);
        AccountUtil::addEnabledMods($accountIds, $moduleNames);
        AccountUtil::updateAvailableExtMods($accountIds, $moduleNames);
        AccountUtil::clearCache();
        foreach ($moduleNames as $moduleName) {
            AccountUtil::execute($moduleName, $accountIds);
        }
    }

    public function actionUninstall($modules, $accountIds = [])
    {
        $condition = [];
        if (!empty($accountIds)) {
            $ids = explode(',', $accountIds);
            $MSG = 'Uninstall modules for ';
            AccountUtil::hint($MSG);
            AccountUtil::info(json_encode($ids));
            $accountIds = AccountUtil::getMongoIds($ids);
            unset($ids);
        } else {
            $accountIds = AccountUtil::getAllAccount();
            $MSG = 'Install module for all account.';
            AccountUtil::warning($MSG);
        }
        if (!empty($modules)) {
            $mods = explode(',', $modules);
        } else {
            $MSG = 'invalid parameters, modules must be required.';
            AccountUtil::error($MSG);
            return;
        }

        $MSG = 'These modules will be uninstall:';
        AccountUtil::hint($MSG);
        AccountUtil::info(json_encode($mods));
        $results = Yii::$app->extModule->getMergedConfig($mods);

        $MSG = 'All message show as follow:';
        AccountUtil::hint($MSG);
        AccountUtil::info(json_encode($results));
        AccountUtil::removeFromMenus($accountIds, $mods);
        AccountUtil::removeFromMods($accountIds, $results['mods']);
        AccountUtil::removeFromEnabledMods($accountIds, $mods);
        AccountUtil::removeFromAvailableExtMods($accountIds, $mods);
        AccountUtil::clearCache();
    }

    /**
     * Method for struct change #4107
     * 1. add mall and marketing to availableExtMods.
     * 2. add mall and marketing to enabledMods if account enabled prouct
     * 3. if account has not enable product, member, microsite
     * 5. remove product, member and microsite from availableExtMods.
     */
    public function actionMigrationForModuleChange()
    {
        //add new extMods
        $newExtMods = ['mall', 'marketing'];
        Account::updateAll(['$addToSet' => ['availableExtMods' => ['$each' => $newExtMods]]]);
        //add new extMods to enabledMods if account has enabled
        Account::updateAll(
            ['$addToSet' => ['enabledMods' => ['$each' => $newExtMods]]],
            ['enabledMods' => 'product']
        );

        $oldExtMods = ['product', 'member', 'microsite'];
        $accounts = Account::findAll([]);
        foreach ($accounts as $account) {
            foreach ($oldExtMods as $oldExtMod) {
                if (!in_array($oldExtMod, $account->enabledMods)) {
                    try {
                        $this->actionInstall($oldExtMod, (string) $account->_id);
                    } catch (\Exception $e) {
                        echo 'Error account: ' . $account->_id . PHP_EOL;
                        echo 'Message: ' . $e->getMessage() . PHP_EOL;
                    }
                }
            }
        }

        //remove product, member and microsite, form extMods
        foreach ($oldExtMods as $oldExtMod) {
            Account::updateAll(['$pull' => ['availableExtMods' => $oldExtMod]]);
        }
    }

    public function actionRunInstall($moduleName, $accountIds)
    {
        if ($accountIds != 'all') {
            $ids = explode(',', $accountIds);
            $MSG = 'Uninstall modules for ';
            AccountUtil::hint($MSG);
            AccountUtil::info(json_encode($ids));
            $accountIds = AccountUtil::getMongoIds($ids);
            unset($ids);
        } else {
            $accountIds = AccountUtil::getAllAccount();
            $MSG = 'Install module for all account.';
            AccountUtil::warning($MSG);
        }
        AccountUtil::execute($moduleName, $accountIds);
    }
}
