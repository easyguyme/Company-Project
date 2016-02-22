<?php
return [
    'defaultAvatar' => 'https://dn-quncrm.qbox.me/image_hover_default_avatar.png',
    'channelLimit' => 4,
    'default_subscribe_reply' => 'Hi,欢迎来到{name}{channel}平台!',
    'default_resubscribe_reply' => 'Hi,欢迎再次回到{name}{channel}平台!',
    'default_default_reply' => 'Hi,{name}{channel}平台，有什么可以帮助你的？',
    'helpdesk_max_wait_time' => 3,
    'helpdesk_max_clients' => 5,
    'helpdesk_onduty_time' => '8:00',
    'helpdesk_offduty_time' => '18:00',
    'helpdesk_system_replies' => [
        ['name' => 'wait_for_service', 'type' => 'waitting', 'replyText' => '正在分配客服人员，请耐心等待', "isEnabled" => true],
        ['name' => 'close_service', 'type' => 'close', 'replyText' => '客服已经关闭当前会话', "isEnabled" => true],
        ['name' => 'non_working_time', 'type' => 'nonworking', 'replyText' => '当前不是工作时间', "isEnabled" => true],
        ['name' => 'auto_brake', 'type' => 'brake', 'replyText' => '该会话已自动断开，请重新连接', "isEnabled" => true],
        ['name' => 'connect_success', 'type' => 'success', 'replyText' => '已分配客服为您服务', "isEnabled" => true],
        ['name' => 'desk_droping', 'type' => 'droping', 'replyText' => 'Opps,客服掉线了', "isEnabled" => true],
        ['name' => 'system_error', 'type' => 'error', 'replyText' => '抱歉,系统异常，请联系管理员', "isEnabled" => true]
    ],
    'socket_timeout' => 30,
    'pending_interval' => 5,
    'card_number_count' => 10000000,
    'captcha_send_interval' => 60,//seconds
    'captcha_availab_time' => 43200,//43200 = 12h * 60m * 60seconds
    'img_captcha_availab_time' => 3600,//3600 = 1*60*60seconds
    'mobile_message_text' => '【#company#】您的验证码是#code#。如非本人操作，请忽略本短信',
    'WeconnectCSSessionExpire' => 7200000, // 2 hours
    'member_max_score' => 99999999,
    'user_active_link_availab_time' => 7, // days
    'PUSH_MESSAGE_MAX_SAVE_TIME' => 0,
];
