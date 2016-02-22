<?php

namespace backend\modules\channel\modules\offlineStore;

use backend\models\User;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\channel\modules\offlineStore\controllers';

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
}
