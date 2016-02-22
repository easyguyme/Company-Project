<?php
namespace backend\modules\common;

use backend\models\User;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\common\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [
        User::ROLE_ADMIN,
        User::ROLE_OPERATOR,
        User::ROLE_CUSTOMER_SERVICE,
        USER::ROLE_MOBILE_ENDUSER
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * set monogo collection index
     */
    public static function setCollectionIndex()
    {
        return [
            [
                'class' => '\backend\models\Qrcode',
                'indexes' => [
                    [
                        'keys' => ['associatedId'],
                        'options' => [],
                    ]
                ]
            ],
            [
                'class' => '\backend\models\Captcha',
                'indexes' => [
                    [
                        'keys' => ['createdAt'],
                        'options' => ['expireAfterSeconds' => 3600*24*30],
                    ]
                ]
            ],
            [
                'class' => '\backend\models\Token',
                'indexes' => [
                    [
                        'keys' => ['expireTime'],
                        'options' => ['expireAfterSeconds' => 3600*24*15],
                    ]
                ]
            ],
            [
                'class' => '\backend\models\GameToken',
                'indexes' => [
                    [
                        'keys' => ['validTo'],
                        'options' => ['expireAfterSeconds' => 3600*24],
                    ],
                    [
                        'keys' => ['memberId'],
                        'options' => [],
                    ]
                ]
            ],
            [
                'class' => '\backend\models\StatsMemberCampaignLogDaily',
                'indexes' => [
                    [
                        'keys' => ['productId' => 1, 'code' => 1, 'month' => 1],
                        'options' => [],
                    ]
                ],
            ]
        ];
    }
}
