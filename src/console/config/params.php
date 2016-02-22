<?php
return [
    'adminEmail' => 'admin@example.com',
    'member_max_score' => 99999,
    'indexes' => [[
        'class' => '\backend\modules\member\models\Member',
        'indexes' => [[
            'keys' => ['cardNumber'],
            'options' => ['unique' => true],
        ], [
            'keys' => ['accountId', 'isDeleted', 'createdAt'],
            'options' => [],
        ], [
            'keys' => ['cardId', 'isDeleted'],
            'options' => [],
        ],
        [
            'keys' => ['accountId', 'isDeleted', 'phone'],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\models\Qrcode',
        'indexes' => [[
            'keys' => ['associatedId'],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\modules\product\models\PromotionCode',
        'indexes' => [[
            'keys' => ['code', 'accountId'],
            'options' => ['unique' => true],
        ], [
            'keys' => ['productId', 'code'],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\modules\product\models\CampaignLog',
        'indexes' => [[
            'keys' => ['accountId' => 1, 'createdAt' => -1],
            'options' => [],
        ], [
            'keys' => ['campaignId', 'productId', 'member.id', 'createdAt', 'accountId'],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\modules\product\models\GoodsExchangeLog',
        'indexes' => [[
            'keys' => ['accountId' => 1, 'createdAt' => -1],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\modules\member\models\ScoreHistory',
        'indexes' => [[
            'keys' => ['memberId' => 1, 'createdAt' => -1],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\models\Captcha',
        'indexes' => [[
            'keys' => ['createdAt'],
            'options' => ['expireAfterSeconds' => 3600*24*30],
        ]],
    ], [
        'class' => '\backend\models\Token',
        'indexes' => [[
            'keys' => ['expireTime'],
            'options' => ['expireAfterSeconds' => 3600*24*15],
        ]],
    ], [
        'class' => '\backend\models\GameToken',
        'indexes' => [[
            'keys' => ['validTo'],
            'options' => ['expireAfterSeconds' => 3600*24],
        ],[
            'keys' => ['memberId'],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\modules\game\models\GameLog',
        'indexes' => [[
            'keys' => ['accountId' => 1, 'gameId' => 1, 'createdAt' => -1],
            'options' => [],
        ]],
    ], [
        'class' => '\backend\models\StatsMemberCampaignLogDaily',
        'indexes' => [[
            'keys' => ['productId' => 1, 'code' => 1, 'month' => 1],
            'options' => [],
        ]],
    ]],
];
