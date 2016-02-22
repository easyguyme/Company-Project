<?php
// State can not contains the parameter placeholder like {id}
return [
    'name' => 'channel',
    'order' => 1,
    'isInTopNav' => true,
    'isCore' => true,
    'menusConfig' => [
        'channel' => [
            [
                'order'  => 1,
                'title'  => 'follower_management',
                'name' => 'follower',
                'state' => 'channel-follower'
            ],
            [
                'order' => 2,
                'title' => 'broadcast',
                'name' => 'broadcast',
                'state' => 'channel-broadcast'
            ],
            [
                'order'  => 3,
                'title'  => 'interactive_messages',
                'name' => 'interaction',
                'state' => 'channel-interaction'
            ],
            [
                'order'  => 4,
                'title'  => 'customized_menus',
                'name' => 'menu',
                'state' => 'channel-menu'
            ],
            [
                'order'  => 5,
                'title'  => 'auto_reply',
                'name' => 'autoreply',
                'state' => 'channel-autoreply'
            ],
            [
                'order'  => 6,
                'title'  => 'promotion_qrcode',
                'name' => 'qrcode',
                'state' => 'channel-qrcode'
            ],
            [
                'order' => 7,
                'title' => 'channel_payment',
                'name' => 'channelpayment',
                'state' => 'channel-payment'
            ]
        ]
    ]
];
