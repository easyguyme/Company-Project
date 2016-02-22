<?php
namespace backend\modules\content;

use backend\models\User;
use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\content\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_MOBILE_ENDUSER];

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
                'class' => 'backend\modules\content\job\StatsQuestionnaireDaily',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\content\job\StatsQuestionnaireAnswerDaily',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ]
        ];
    }
}
