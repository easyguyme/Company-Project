<?php

return [
    'name' => 'member',
    'namezh' => '会员',
    'isInTopNav' => true,
    'isCore' => true,
    'order' => 2,
    'menusConfig' => [
        'member' => [
            [
                'order' => 1,
                'title' => 'member_management',
                'name' => 'member',
                'state' => 'member-member'
            ],
            [
                'order' => 2,
                'title' => 'member_card',
                'name' => 'card',
                'state' => 'member-card'
            ],
            [
                'order' => 3,
                'title' => 'member_incentive',
                'name' => 'incentive',
                'state' => 'member-incentive'
            ]
        ],
        'analytic' => [
            [
                'order' => 4,
                'title' => 'member_analytic',
                'name' => 'statmember',
                'state' => 'member-analytic'
            ]
        ]
    ]
];
