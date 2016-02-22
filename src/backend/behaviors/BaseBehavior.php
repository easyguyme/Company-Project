<?php
namespace backend\behaviors;

use yii\base\Behavior;
use backend\utils\LogUtil;
use Yii;

class BaseBehavior extends Behavior
{
    /**
     * Execute the handle method in events folder of PortalEvent class
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    protected function notifyModules($data)
    {
        $modulePath = Yii::getAlias('@backend') . DIRECTORY_SEPARATOR . 'modules';
        $filenames = scandir($modulePath);
        foreach ($filenames as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_file($classFile = $modulePath . DIRECTORY_SEPARATOR . $filename . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR . 'PortalEvent.php')) {
                $class = 'backend\modules\\' . $filename . '\events\PortalEvent';
                $object = new $class();
                call_user_func([$object, 'handle'], $data);
            }
        }
    }
}
