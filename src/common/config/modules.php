<?php
function initModules($application)
{
    $redis = new Redis();
    $redis->connect(CACHE_HOSTNAME, CACHE_PORT);
    if (defined('CACHE_PASSWD') && !empty(CACHE_PASSWD)) {
         $redis->auth(CACHE_PASSWD);
     }
    $redis->select(CACHE_DB);
    $modulesKey = $application . '-modules';
    $modules = $redis->get($modulesKey);

    if (empty($modules)) {
        $modules = [];
        $dir = __DIR__ . '/../../' . $application . '/modules';
        $dirpath = realpath($dir);
        $filenames = scandir($dir);

        foreach ($filenames as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_file($dirpath . DIRECTORY_SEPARATOR . $filename . '/Module.php')) {
                $modules[strtolower($filename)] = [
                    'class' => $application . '\modules\\' . $filename . '\Module'
                ];
            }
        }

        $redis->set($modulesKey, serialize([$modules, null]));
    } else {
        $modules = unserialize($modules);

        if (!empty($modules[0])) {
            $modules = $modules[0];
        }
    }

    $redis->close();
    return $modules;
}
