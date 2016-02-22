<?php

return [
    'name' => 'management',
    'order' => 1,
    'isInTopNav' => false,
    'isCore' => true,
    'menusConfig' => [
        'management' => [
            [
                'order' => 1,
                'title' => 'user_management',
                'name' => 'user',
                'state' => 'management-user'
            ],
            [
                'order' => 2,
                'title' => 'sensitive_management',
                'name' => 'sensitive',
                'state' => 'management-sensitive'
            ],
            [
                'order' => 3,
                'title' => 'channel_management',
                'name' => 'channel',
                'state' => 'management-channel'
            ],
            [
                'order' => 4,
                'title' => 'store_management',
                'name' => 'store',
                'state' => 'management-store'
            ],
            [
                'order' => 5,
                'title' => 'customer_property',
                'name' => 'attribute',
                'state' => 'management-setting'
            ],
            [
                'order' => 6,
                'title' => 'extension_management',
                'name' => 'extension',
                'state' => 'management-extension'
            ],
            [
                'order' => 7,
                'title' => 'enterprise_management',
                'name' => 'enterprise',
                'state' => 'management-enterprise'
            ],
            [
                'order' => 8,
                'title' => 'token_management',
                'name' => 'token',
                'state' => 'management-token'
            ],
            /*
            [
                'order' => 9,
                'title' => 'ding_auth_management',
                'name' => 'ding',
                'state' => 'management-ding'
            ],

            [
                'order' => 6,
                'title' => 'updation_management',
                'name' => 'updation',
                'state' => 'management-updation'
            ],
            [
                'order' => 7,
                'title' => 'upgrade_management',
                'name' => 'upgrade',
                'state' => 'management-upgrade'
            ]
            */
        ]
    ]
];
