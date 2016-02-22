<?php
namespace backend\modules\channel;

use Yii;
use backend\models\User;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\channel\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR];

    public function init()
    {
        parent::init();

        $this->modules = [
            'offlinestore' => [
                'class' => 'backend\modules\channel\modules\offlineStore\Module',
            ],
        ];
    }
}
