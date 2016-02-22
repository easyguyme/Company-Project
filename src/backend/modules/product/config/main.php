<?php

return [
    'name' => 'product',
    'namezh' => '商品',
    'order' => 5,
    'isInTopNav' => true,
    'isCore' => true,
    'menusConfig' => [
        'product' => [
            [
                'order' => 1,
                'title' => 'product_management',
                'name' => 'product',
                'icon' => 'product',
                'state' => 'product-product'
            ],
            [
                'order' => 2,
                'title' => 'promotion_code',
                'name' => 'promotion',
                'icon' => 'promotion',
                'state' => 'product-promotion'
            ],
            [
                'order' => 3,
                'title' => 'product_setting',
                'name' => 'setting',
                'icon' => 'setting',
                'state' => 'product-setting'
            ]
        ],
        'store' => [
            [
                'order' => 3,
                'title' => 'goods_shelf',
                'name' => 'shelf',
                'state' => 'store-shelf'
            ]
        ]
    ]
];
