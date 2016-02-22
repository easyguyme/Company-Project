<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class ExtModule extends Component
{
    public function getMergedConfig($modNames)
    {
        $result = ['menus' => [], 'mods' => []];
        if (!empty($modNames)) {
            $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
            $modulesPath = realpath($modulesDIR);
            foreach ($modNames as $modName) {
                $configPath = $modulesPath . DIRECTORY_SEPARATOR . $modName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';
                if (is_file($configPath)) {
                    $moduleConfig = require($configPath);

                    if (isset($moduleConfig['menusConfig']) && !empty($moduleConfig['menusConfig'])) {
                        foreach ($moduleConfig['menusConfig'] as $moduleName => $moduleItems) {
                            if (!isset($result['menus'][$moduleName])) {
                                $result['menus'][$moduleName] = [];
                            }
                            $result['menus'][$moduleName] = ArrayHelper::merge($result['menus'][$moduleName], $moduleItems);
                        }
                    }

                    if (isset($moduleConfig['isInTopNav']) && $moduleConfig['isInTopNav']) {
                        $result['mods'][] = [
                            'name' => $moduleConfig['name'],
                            'isCore' => $moduleConfig['isCore'],
                            'order' => $moduleConfig['order']
                        ];
                    }
                }
            }
            foreach ($result['menus'] as &$moduleItems) {
                ArrayHelper::multisort($moduleItems, 'order', SORT_ASC);
            }
            ArrayHelper::multisort($result['mods'], 'order', SORT_ASC);
        }

        return $result;
    }

    public function getMenuAndExtName($modNames)
    {
        $result = ['menuNames' => [], 'extNames' => []];

        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        foreach ($modNames as $modName) {
            $configPath = $modulesPath . DIRECTORY_SEPARATOR . $modName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';
            if (is_file($configPath)) {
                $moduleConfig = require($configPath);

                if (isset($moduleConfig['menusConfig']) && !empty($moduleConfig['menusConfig'])) {
                    foreach ($moduleConfig['menusConfig'] as $moduleName => $moduleItems) {
                        foreach ($moduleItems as $moduleItem) {
                            $result['menuNames'][] = $moduleItem['name'];
                        }
                    }
                }

                if (!isset($moduleConfig['isCore']) || !$moduleConfig['isCore']) {
                    $result['extNames'][] = $moduleConfig['name'];
                }
            }
        }

        return $result;
    }

    public function getMenuAndExt($modName)
    {
        $menus = [];
        $mods = [];

        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        $configPath = $modulesPath . DIRECTORY_SEPARATOR . $modName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';

        if (is_file($configPath)) {
            $moduleConfig = require($configPath);

            if (isset($moduleConfig['menusConfig']) && !empty($moduleConfig['menusConfig'])) {
                $menus = $moduleConfig['menusConfig'];
                foreach ($moduleConfig['menusConfig'] as $moduleName => $moduleItems) {
                    $configPath = $modulesPath . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';

                    if (is_file($configPath)) {
                        $config = require($configPath);

                        if (isset($config['isInTopNav']) && $config['isInTopNav']) {
                            $mods[] = [
                                'name' => $config['name'],
                                'isCore' => $config['isCore'],
                                'order' => $config['order']
                            ];
                        }
                    }
                }
            }
        }

        return [$menus, $mods];
    }


    public function getList()
    {
        $result = [];
        $modulesDIR = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $modulesPath = realpath($modulesDIR);
        $filenames = scandir($modulesDIR);

        foreach ($filenames as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_file($modulesPath . DIRECTORY_SEPARATOR . $filename . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php')) {
                $config = require($modulesPath . DIRECTORY_SEPARATOR . $filename . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php');
                if (!isset($config['isCore']) || !$config['isCore']) {
                    $item = [
                        'name' => $config['name'],
                        'type' => 'EXTRA',
                        'icon' => DOMAIN . '/images/' . $config['name'] . '/introduction/icon_default.png',
                        'namezh' => $config['namezh']
                    ];
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    public function getDependencyModules($modules)
    {
        if (is_string($modules)) {
            $modules = [$modules];
        }
        if (!is_array($modules)) {
            return [];
        }
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
            }
        }
        if (!empty(array_diff($moduleNames, $modules))) {
            return $this->getDependencyModules($moduleNames);
        }
        return $moduleNames;
    }
}
