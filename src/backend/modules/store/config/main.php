<?php
return [
    'name' => 'store',
    'order' => 7,
    'isInTopNav' => true,
    'isCore' => true,
    'menusConfig' => [
        'store' => [
            [
                'order' => 1,
                'title' => 'store_info',
                'name' => 'storeinfo',
                'state' => 'store-info'
            ],
            [
                'order'  => 2,
                'title'  => 'order_management',
                'name' => 'ordermanagement',
                'state' => 'store-order'
            ],
            [
                'order'  => 4,
                'title'  => 'staff_management',
                'name' => 'staff',
                'state' => 'store-staff'
            ]
        ]
    ]
];
