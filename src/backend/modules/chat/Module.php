<?php
namespace backend\modules\chat;

use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\chat\controllers';

    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }

    /**
     * set job to run by schedule time
     */
    public static function setScheduleJob()
    {
        return [
            [
                'class' => 'backend\modules\chat\job\ClearOffline',
                'interval' => TimeUtil::SECONDS_OF_MINUTE,
                'executeAt' => 'Y-m-d H:i:00'
            ]
        ];
    }
}
