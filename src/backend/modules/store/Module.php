<?php
namespace backend\modules\store;

use backend\models\User;
use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\store\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR];

    public function init()
    {
        parent::init();
    }

      /**
     * set job to run by schedule time
     */
    public static function setScheduleJob()
    {
        return [
            [
                'class' => 'backend\modules\store\job\StoreGoodsOnSale',
                'interval' => TimeUtil::SECONDS_OF_MINUTE,
                'executeAt' => 'Y-m-d H:i:00'
            ],
        ];
    }
}
