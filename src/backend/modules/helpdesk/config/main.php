<?php

return [
    'name' => 'helpdesk',
    'namezh' => '客服',
    'order' => 4,
    'isInTopNav' => true,
    'isCore' => false,
    'menusConfig' => [
        'helpdesk' => [
            [
                'order' => 1,
                'title' => 'helpdesk_account',
                'name' => 'helpdesk',
                'state' => 'helpdesk-helpdesk'
            ],
            [
                'order' => 2,
                'title' => 'wiki',
                'name' => 'wiki',
                'state' => 'helpdesk-wiki'
            ],
            [
                'order' => 3,
                'title' => 'helpdesk_self_service',
                'name' => 'helpdeskselfservice',
                'state' => 'helpdesk-helpdeskselfservice'
            ],
            [
                'order' => 4,
                'title' => 'session_management',
                'name' => 'session',
                'state' => 'helpdesk-session'
            ],
            [
                'order' => 5,
                'title' => 'helpdesk_vip',
                'name' => 'helpdeskvip',
                'state' => 'helpdesk-helpdeskvip'
            ],
            [
                'order' => 6,
                'title' => 'helpdesk_settings',
                'name' => 'setting',
                'state' => 'helpdesk-setting'
            ],
            [
                'order' => 7,
                'title' => 'helpdesk_login_chat_system',
                'name' => 'login',
                'link' => '/chat/login',
                'class' => 'btn-helpdesk-login btn-success',
                'centered' => true
            ]
        ]
    ]
];
