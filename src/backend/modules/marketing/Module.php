<?php
namespace backend\modules\marketing;

use backend\models\User;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\marketing\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_MOBILE_ENDUSER];

    public function init()
    {
        parent::init();
    }
}
