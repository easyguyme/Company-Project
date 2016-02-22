<?php
return [
    'name' => 'member',
    'title' => 'channel_menu_member',
    'introductions' => ['channel_menu_member_tip1', 'channel_menu_member_tip2', 'channel_menu_member_tip3', 'channel_menu_member_tip4'],
    'keycode' => 'USER_CENTER', // default is 'MEMBER'
    'news' => [
        'others' => [
            'type' => 'VIEW', // 'VIEW' 'CLICK'
            'msgType' => 'URL', // 'URL' / 'TEXT' or 'NEWS'
            'content' => DOMAIN . 'api/mobile/member?appId={{appId}}&channelId={{channelId}}',
            'dataCallback' => ['\backend\modules\member\utils\Conf', 'getContentInfo'],
        ],
        'subscription' => [
            'type' => 'CLICK', // 'VIEW' 'CLICK'
            'msgType' => 'NEWS', // 'URL' / 'TEXT' or 'NEWS'
            'content' => [
                'articles' => [[
                    'title' => '会员中心',
                    'picUrl' => DOMAIN . 'images/member/member_center_graphic.png',
                    'description' => '点击图文进入会员中心',
                    'contentUrl' => DOMAIN . 'api/mobile/check-bind?state={{channelId}}',
                    'sourceUrl' => DOMAIN . 'api/mobile/check-bind?state={{channelId}}',
                ]]
            ],
            'dataCallback' => ['\backend\modules\member\utils\Conf', 'getChannelId'],
        ]
    ],
    'newsCallback' => ['\backend\modules\member\utils\Conf', 'pickNews'],
    'isEnabled' => true,
];
