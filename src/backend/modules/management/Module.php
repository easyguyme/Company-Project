<?php
namespace backend\modules\management;
use backend\models\User;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\management\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN];

    public function init()
    {
        parent::init();
    }

}
