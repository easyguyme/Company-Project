<?php

namespace backend\modules\helpdesk;

use backend\models\User;
use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\helpdesk\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR];

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
                'class' => 'backend\modules\helpdesk\job\StatsConversation',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 00:00:01'
            ]
        ];
    }
}
