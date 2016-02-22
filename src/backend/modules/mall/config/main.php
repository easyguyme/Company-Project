<?php

return [
    'name' => 'mall',
    'namezh' => '积分商城',
    'order' => 8,
    'isInTopNav' => true,
    'isCore' => false,
    'menusConfig' => [
        'mall' => [
            [
                'order' => 1,
                'title' => 'shelf_management',
                'name' => 'layout',
                'icon' => 'layout',
                'state' => 'mall-goods'
            ],
            [
                'order' => 2,
                'title' => 'offline_exchange',
                'name' => 'offline',
                'icon' => 'offline',
                'state' => 'mall-offline'
            ],
            [
                'order' => 3,
                'title' => 'mall_redemption',
                'name' => 'redemption',
                'icon' => 'redemption',
                'state' => 'mall-redemption'
            ]
        ]
    ]
];
