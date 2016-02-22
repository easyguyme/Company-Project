<?php

return [
    'name' => 'content',
    'order' => 3,
    'isInTopNav' => true,
    'isCore' => true,
    'menusConfig' => [
        'content' => [
            [
                'order' => 1,
                'title' => 'graphics_content',
                'name' => 'graphics',
                'state' => 'content-graphics'
            ],
            [
                'order' => 3,
                'title' => 'questionnaire',
                'name' => 'questionnaire',
                'state' => 'content-questionnaire'
            ]
        ]
    ]
];
