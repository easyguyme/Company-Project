<?php
namespace console\utils;

use Yii;
use backend\models\Account;
use MongoId;
use yii\helpers\Console;

class AccountUtil
{
    /**
     * Scan module in backend.
     * @return array $moduleNames
     */
    public static function getModules($modules)
    {
        $moduleNames = [];
        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        foreach ($modules as $modName) {
            $configPath = $modulesPath . DIRECTORY_SEPARATOR . $modName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';
            if (is_file($configPath)) {
                if (!in_array($modName, $moduleNames)) {
                    array_push($moduleNames, $modName);
                }
                $moduleConfig = require($configPath);

                if (isset($moduleConfig['menusConfig']) && !empty($moduleConfig['menusConfig'])) {
                    foreach ($moduleConfig['menusConfig'] as $moduleName => $moduleItems) {
                        if (!in_array($moduleName, $moduleNames)) {
                            array_push($moduleNames, $moduleName);
                        }
                    }
                }
            } else {
                self::warning('No such moduleNames: '.$modName);
            }
        }
        return $moduleNames;
    }

    /**
     * Get menus from module configuration file
     * @param  string $module the name of module
     * @return array the configuration for module menus
     */
    public static function getMenusOfModule($module)
    {
        $moduleDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $configPath = $moduleDIR . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';

        if (is_file($configPath)) {
            $moduleConfig = require($configPath);

            if (!empty($moduleConfig['menusConfig'])) {
                return $moduleConfig['menusConfig'];
            } else {
                self::warning("Invalid module configuration lack of menusConfig field: $module");
            }
        } else {
            self::warning("No such module: $module");
        }
        return [];
    }

    /**
     * 在enableMods里面添加当前待激活模块
     * @param array $accountIds
     * @param array $enabledMods
     */
    public static function addEnabledMods($accountIds = [], $enabledMods = [])
    {
        if (empty($accountIds) || empty($enabledMods)) {
            return;
        }
        if (!is_array($accountIds) || !is_array($enabledMods)) {
            return;
        }
        $MSG = 'Add module in enabledMods :';
        self::hint($MSG);
        self::info(json_encode($enabledMods));
        $condition['_id']['$in'] = $accountIds;
        $update['$addToSet']['enabledMods'] = ['$each' => $enabledMods];
        $MSG = 'The query condition is :';
        self::hint($MSG);
        self::info(json_encode($condition));
        $MSG = 'The execute condition is :';
        self::hint($MSG);
        self::info(json_encode($update));
        $collection = Account::getCollection();
        return $collection->update($condition, $update);
    }

    /**
     * 将账号下的mods的字段更新
     * @param  array  $accountIds
     * @param  array  $mods
     * @return
     */
    public static function updateMods($accountIds = [], $mods = [])
    {
        if (empty($accountIds) || empty($mods)) {
            echo 'accountIds or mods is empty';
            return;
        }
        if (!is_array($accountIds) || !is_array($mods)) {
            echo 'accountIds or $mods is empty ';
            return;
        }
        $MSG = 'Add modules in mods :';
        self::hint($MSG);
        self::info(json_encode($mods));
        $condition['_id']['$in'] = $accountIds;
        $update['$addToSet']['mods'] = ['$each' => $mods];
        $MSG = 'The query condition is :';
        self::hint($MSG);
        self::info(json_encode($condition));
        $MSG = 'The execute condition is :';
        self::hint($MSG);
        self::info(json_encode($update));
        $collection = Account::getCollection();
        return $collection->update($condition, $update);
    }

    /**
     * 将账号下的menus字段更新
     * @param  array $accountIds
     * @param  array $menus
     * @return
     */
    public static function updateMenus($accountIds, $menus)
    {
        if (empty($accountIds) || empty($menus)) {
            echo 'accountIds or menus is empty';
            return;
        }
        if (!is_array($accountIds) || !is_array($menus)) {
            echo 'accountIds or menus is empty ';
            return;
        }
        $MSG = 'Add modules menu in menus :';
        self::hint($MSG);
        self::info(json_encode($menus));
        $condition['_id']['$in'] = $accountIds;
        $update = [];
        foreach ($menus as $key => $value) {
            $update['$addToSet']['menus.' . $key] = ['$each' => $value];
        }
        $MSG = 'The query condition is :';
        self::hint($MSG);
        self::info(json_encode($condition));
        $MSG = 'The execute condition is :';
        self::hint($MSG);
        self::info(json_encode($update));
        $collection = Account::getCollection();
        return $collection->update($condition, $update);
    }

     /**
     * 将账号下的availableExtMods字段更新
     * @param  array $accountIds
     * @param  array $modules
     * @return
     */
    public static function updateAvailableExtMods($accountIds, $modules)
    {
        if (empty($accountIds) || empty($modules)) {
            echo 'accountIds or modules is empty';
            return;
        }
        if (!is_array($accountIds) || !is_array($modules)) {
            echo 'accountIds or modules is not array ';
            return;
        }
        $MSG = 'Add modules  in availableExtMods :';
        self::hint($MSG);
        self::info(json_encode($modules));
        $condition['_id']['$in'] = $accountIds;
        $update = [];
        $update['$addToSet']['availableExtMods'] = ['$each' => $modules];
        $MSG = 'The query condition is :';
        self::hint($MSG);
        self::info(json_encode($condition));
        $MSG = 'The execute condition is :';
        self::hint($MSG);
        self::info(json_encode($update));
        $collection = Account::getCollection();
        return $collection->update($condition, $update);
    }

    /**
     * Convert string array to MongoId array.
     * @param  array  $accountIds
     * @return array  $ids
     */
    public static function getMongoIds($accountIds = [])
    {
        $MSG = 'Convert string id to MongoId';
        self::hint($MSG);
        foreach ($accountIds as $id) {
            $ids[] = new MongoId($id);
        }
        return $ids;
    }

    /**
     * Execute run method of Install.php
     * @param  string $moduleName
     * @param  array $accountIds
     * @return
     */
    public static function execute($moduleName, $accountIds)
    {
        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        $installPath = $modulesPath . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Install.php';
        if (is_file($installPath)) {
            $installClassName = 'backend\modules\\' . $moduleName . '\Install';
            self::hint('Execute :' . $installPath);
            $class = new \ReflectionClass($installClassName);
            if (!$class->hasMethod('run')) {
                return;
            } else {
                self::hint($installPath . ' has method run ..');
            }
            $install = $class->newInstanceArgs();
            foreach ($accountIds as $accountId) {
                $install->run($accountId);
            }
        }
    }

    public static function removeFromMenus($accountIds, $modules)
    {
        if (empty($accountIds) || empty($modules)) {
            echo 'accountIds or modules is empty';
            return;
        }
        $MSG = 'Uninstall modules menu in modules :';
        self::hint($MSG);
        self::info(json_encode($modules));
        $condition['_id']['$in'] = $accountIds;
        $update = [];
        $collection = Account::getCollection();
        foreach ($modules as $value) {
            $update['$unset']['menus.' . $value] = 1;
        }
        self::hint('Update sql:');
        self::info(json_encode($update));
        $collection->update($condition, $update);
    }

    public static function removeFromMods($accountIds, $mods)
    {
        if (empty($mods)) {
            self::hint('No mods for uninstall..');
            return;
        }
        $MSG = 'Uninstall modules in mods :';
        self::hint($MSG);
        self::info(json_encode($mods));
        $condition['_id']['$in'] = $accountIds;
        $collection = Account::getCollection();
        foreach ($mods as $value) {
            $update['$pull']['mods'] = $value;
            $collection->update($condition, $update);
        }
    }

    public static function removeFromEnabledMods($accountIds, $modules)
    {
        self::hint('Uninstall enabledMods');
        self::info(json_encode($modules));
        $condition['_id']['$in'] = $accountIds;
        $update = [];
        $collection = Account::getCollection();
        foreach ($modules as $value) {
            $update['$pull']['enabledMods'] = $value;
            $collection->update($condition, $update);
        }
    }

    public static function removeFromAvailableExtMods($accountIds, $modules)
    {
        self::hint('Uninstall availableExtMods');
        self::info(json_encode($modules));
        $condition['_id']['$in'] = $accountIds;
        $collection = Account::getCollection();
        foreach ($modules as $value) {
            $update['$pull']['availableExtMods'] = $value;
            $collection->update($condition, $update);
        }
    }

    public static function clearCache()
    {
        $cache = Yii::$app->cache->redis;
        $apps = ['backend', 'console', 'webapp'];
        foreach ($apps as $app) {
            $key = $app . '-modules';
            $cache->del($key);
            self::warning('Clear cache for key' . $key);
        }
    }

    /**
     * Get all accountId
     * @return array
     */
    public static function getAllAccount()
    {
        $accounts = Account::findAll([]);
        $ids = [];
        foreach ($accounts as $account) {
            $ids[] = $account->_id;
        }
        return $ids;
    }

    public static function info($msg)
    {
        LogUtil::success($msg);
    }

    public static function error($msg)
    {
        LogUtil::error($msg);
    }

    public static function warning($msg)
    {
        LogUtil::warn($msg);
    }

    public static function hint($msg)
    {
        LogUtil::info($msg);
    }
}
