<?php
return [
    [
        'name' => 'helpdesk',
        'title' => 'channel_menu_helpdesk',
        'introductions' => ['channel_menu_helpdesk_tip1', 'channel_menu_helpdesk_tip2'],
        'keycode' => 'CUSTOMER_SERVICE', // default is 'HELPDESK'
        'type' => 'CLICK', // 'VIEW' 'CLICK'
        'msgType' => '',
        'content' => false,
        'isEnabled' => ['\backend\modules\helpdesk\utils\Conf', 'isHelpDeskSet'],
    ],
    [
        'name' => 'feedback',
        'title' => 'channel_menu_helpdesk_feedback',
        'introductions' => ['channel_menu_helpdesk_feedback_tip1', 'channel_menu_helpdesk_feedback_tip2'],
        'keycode' => 'CUSTOMER_FEEDBACK', // default is 'HELPDESK'
        'type' => 'VIEW', // 'VIEW' 'CLICK';
        'msgType' => 'URL',
        'content' => DOMAIN . 'api/mobile/feedback?appId={{appId}}&channelId={{channelId}}',
        'dataCallback' => ['\backend\modules\helpdesk\utils\Conf', 'getContentInfo'],
        'isEnabled' => ['\backend\modules\helpdesk\utils\Conf', 'isHelpDeskSet'],
   ]
];
