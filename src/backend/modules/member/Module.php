<?php

namespace backend\modules\member;

use backend\models\User;
use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\member\controllers';
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
     * set monogo collection index
     */
    public static function setCollectionIndex()
    {
        return [
            [
                'class' => '\backend\modules\member\models\Member',
                'indexes' => [
                    [
                        'keys' => ['cardNumber'],
                        'options' => ['unique' => true],
                    ],
                    [
                        'keys' => ['accountId', 'isDeleted', 'createdAt'],
                        'options' => [],
                    ],
                    [
                        'keys' => ['cardId', 'isDeleted'],
                        'options' => []
                    ]
                ]
            ],
            [
                'class' => '\backend\modules\member\models\ScoreHistory',
                'indexes' => [
                    [
                        'keys' => ['memberId' => 1, 'createdAt' => -1],
                        'options' => [],
                    ]
                ]
            ]
        ];
    }

    /**
     * set job to run by schedule time
     */
    public static function setScheduleJob()
    {
        return [
            [
                'class' => 'backend\modules\member\job\MessageMemberExpired',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\Birthday',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\Statistics',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\StatsMemberOrder',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\StatsMemberDaily',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\StatsMemberOrder',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\StatsMemberGrowthMonthly',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\StatsMemberGrowthQuarterly',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\member\job\ResetScore',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 23:59:00'
            ]
        ];
    }
}
