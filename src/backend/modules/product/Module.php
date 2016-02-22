<?php
namespace backend\modules\product;

use backend\models\User;
use backend\utils\TimeUtil;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\product\controllers';
    /**
     * The accessable roles in this module
     * @var Array the roles array
     */
    public $roleAccess = [User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_MOBILE_ENDUSER];

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
                'class' => '\backend\modules\product\models\PromotionCode',
                'indexes' => [
                    [
                        'keys' => ['code', 'accountId'],
                        'options' => ['unique' => true],
                    ],
                    [
                        'keys' => ['productId', 'code'],
                        'options' => [],
                    ],
                ]
            ],
            [
                'class' => '\backend\modules\product\models\CampaignLog',
                'indexes' => [
                    [
                        'keys' => ['accountId' => 1, 'createdAt' => -1],
                        'options' => [],
                    ],
                    [
                        'keys' => ['campaignId', 'productId', 'member.id', 'createdAt', 'accountId'],
                        'options' => [],
                    ]
                ]
            ],
            [
                'class' => '\backend\modules\product\models\GoodsExchangeLog',
                'indexes' => [
                    [
                        'keys' => ['accountId' => 1, 'createdAt' => -1],
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
                'class' => 'backend\modules\product\job\DisableExpiredCoupon',
                'interval' => TimeUtil::SECONDS_OF_MINUTE,
                'executeAt' => 'Y-m-d H:i:00'
            ],
            [
                'class' => 'backend\modules\product\job\GoodsOnSale',
                'interval' => TimeUtil::SECONDS_OF_MINUTE,
                'executeAt' => 'Y-m-d H:i:00'
            ],
            [
                'class' => 'backend\modules\product\job\CampaignExpired',
                'interval' => TimeUtil::SECONDS_OF_MINUTE,
                'executeAt' => 'Y-m-d H:i:00'
            ],
            [
                'class' => 'backend\modules\product\job\StatsCouponLogDaily',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\product\job\DailyAnalysis',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\product\job\ParticipateAnalysis',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\product\job\TotalAnalysis',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\product\job\TotalParticipateAnalysis',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ],
            [
                'class' => 'backend\modules\product\job\StatsPromotionCodeAnalysis',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00'
            ]
        ];
    }
}
