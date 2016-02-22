<?php
namespace console\controllers;

use yii\console\Controller;
use backend\models\Account;
use yii\helpers\Json;
use Yii;

/**
 * Command line tools for module management (scan, add)
 **/
class ModuleController extends Controller
{
    /**
     * Generate the enabled modules map in the redis cache
     */
    public function actionScan()
    {
        $apps = ['backend', 'console', 'webapp'];
        $cache = Yii::$app->cache->redis;
        foreach ($apps as $app) {
            $key = $app . '-modules';
            $cache->del($key);
            $modules = [];
            $modulesDIR = Yii::getAlias('@' . $app) . DIRECTORY_SEPARATOR . 'modules';
            $modulesPath = realpath($modulesDIR);
            $filenames = scandir($modulesDIR);

            foreach ($filenames as $filename) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                if (is_file($modulesPath . DIRECTORY_SEPARATOR . $filename . '/Module.php')) {
                    $modules[strtolower($filename)] = [
                        'class' => $app . '\modules\\' . $filename . '\Module'
                    ];
                    $moduleNames[] = $filename;
                }
            }

            $cache->set($key, serialize($modules));
        }
    }

    /**
     * Add git submodule configuration and extended modules for soft linking
     * @param  string $name Module name
     */
    public function actionAdd($name = '', $repoPath = '')
    {
        $rootPath = Yii::getAlias('@root');
        //Suppor customize repo path that is not under SCRM namespace
        if (empty($repoPath)) {
            $repoPath = "git@git.augmentum.com.cn:scrm/omnisocials-module-$name.git";
        }
        //Generate git submodule
        system("cd $rootPath");
        system("git submodule add $repoPath modules/$name");
        //Append extended module name in the extModule list
        $packagePath = "$rootPath/package.json";
        $json = file_get_contents($packagePath);
        $packageObj = json_decode($json);
        if (in_array($name, $packageObj->extModules)) {
            echo "Module $name configuration has been generated before!\n";
        } else {
            $packageObj->extModules[] = $name;
            $json = json_encode($packageObj, JSON_PRETTY_PRINT);
            //Leave only 2 spaces indent and add blank line
            $json = str_replace('    ', '  ', $json) . "\n\r";
            $json = str_replace('\/', '/', $json);
            file_put_contents($packagePath, $json);
            echo "Module $name configuration is generated successfully\n";
        }
    }
}
