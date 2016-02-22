<?php

return [
    'name' => 'analytic',
    'order' => 6,
    'isInTopNav' => true,
    'isCore' => true,
    'menusConfig' => [
        'analytic' => [
            [
                'order' => 1,
                'title' => 'analytic_followers_growth',
                'name' => 'growth',
                'state' => 'analytic-growth'
            ],
            [
                'order' => 2,
                'title' => 'analytic_followers_property',
                'name' => 'property',
                'state' => 'analytic-property'
            ],
            [
                'order'=> 3,
                'title' => 'analytic_content_spread',
                'name' => 'content',
                'state' => 'analytic-content'
            ],
            [
                'order'=> 5,
                'title' => 'analytic_store',
                'name' => 'store',
                'state' => 'analytic-store'
            ]
        ]
    ]
];
