<?php
return [
    'coreMods' => ['content', 'analytic', 'management', 'common', 'channel', 'store', 'member', 'product', 'microsite'],
    'default_member_ship_card' => [
        'name' => '默认会员卡',
        'poster' => '/images/mobile/membercard.png',
        'fontColor' => '#fff',
        'privilege' => '<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>',
        'condition' => [
            'minScore' => 0,
            'maxScore' => 100
        ],
        'usageGuide' => '使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有',
        'isEnabled' => true,
        'isDefault' => true
    ],
    'default_cover_page' => [
        'title' => '默认首页',
        'description' => '默认首页',
        'type' => 'cover',
        'isFinished' => true,
        'deletable' => false
    ],
    'default_cover_pagecomponent' => [
        'name' => 'cover1',
        'color' => '#6AB3F7',
        'order' => 0
    ],
    'user.passwordResetTokenExpire' => 3600,
    //Generate an account with default value as below
    'defaultAvatar' => 'https://dn-quncrm.qbox.me/image_hover_default_avatar.png',
    'defaultPwd' => 'abc123_',
    'defaultLanguage' => 'zh_cn',
    'defaultName' => 'admin',
    //Generate an account with default value above
    'micrositeDefaultColor' => '#6AB3F7',
    'micrositeDefaultConfig' => require_once 'microsite.php',
    'sensitive_options' => [
        // Manual Issuance of Points
        'management_manual_issuance_of_points' => [
            'states' => [],
            'actions' => [
                'member/score/give',
            ],
        ],
        // Item Management
        'management_item' => [
            'states' => [],
            'actions' => [
                'product/product/create',
                'product/product/update',
                'product/product/delete'
            ],
        ],
        // Item Management in Points Consumption Store
        'management_item_in_pcs' => [
            'states' => [],
            'actions' => [
                'mall/goods/create',
                'mall/goods/update',
                'mall/goods/delete',
                'mall/goods/update-goods-status',
                'mall/goods-exchange-log/ship'
            ],
        ],
        // Report Statistics
        'management_report_stat' => [
            'states' => [],
            'actions' => [
                'analytic/follower/statistic',
                'analytic/follower/location',
                'analytic/mass-message/interval',
                'analytic/mass-message/index',
                'uhkklp/stats-property/product-code',
                'uhkklp/stats-property/export-product-code',
                'uhkklp/stats-property/member-participant',
                'uhkklp/stats-property/export-member-participant',
                'uhkklp/stats-promotion/product',
                'uhkklp/stats-promotion/export-product',
                'uhkklp/stats-property/code-avg-quarterly',
                'uhkklp/stats-property/export-code-avg-quarterly',
                'uhkklp/stats-property/product-operator-avg',
                'uhkklp/stats-property/export-product-operator-avg',
                'uhkklp/stats-property/member-monthly',
                'uhkklp/stats-property/export-member-monthly',
                'product/promotion-code-analysis/index',
                'product/promotion-code-analysis/export',
                'product/promotion-code-analysis/export-campaign-analysis',
                'member/stats/active-tracking',
                'member/stats/export-active-tracking',
                'member/stats/signup-summary',
                'member/stats/export-signup-summary',
                'member/stats/engagement',
                'member/stats/export-engagement',
            ],
        ],
        // Promo Code Management
        'management_promo_code' => [
            'states' => [],
            'actions' => [
                'product/promotion-code/create',
                'product/promotion-code/export',
                'product/promotion-code/del-history',
                'product/promotion-code/check-code',
            ],
        ],
    ]
];
