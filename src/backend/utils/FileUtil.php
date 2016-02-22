<?php
namespace backend\utils;

use Yii;

class FileUtil
{
    /**
     * @return array, [moduleName => path, moduleName => path]
     * @param string, $application, example:backend,frontend..
     */
    public static function getModule($application)
    {
        $modules = [];
        $dir = Yii::getAlias('@root') . DIRECTORY_SEPARATOR . $application . DIRECTORY_SEPARATOR . 'modules';
        $dirpath = realpath($dir);
        $filenames = scandir($dir);

        foreach ($filenames as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_file($dirpath . DIRECTORY_SEPARATOR . $filename . DIRECTORY_SEPARATOR . 'Module.php')) {
                $modules[strtolower($filename)] = [
                    'class' => $application . '\modules\\' . $filename . '\Module'
                ];
            }
        }
        return $modules;
    }
}
