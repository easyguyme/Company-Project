<?php
return [
    [
        'name' => 'product',
        'title' => 'channel_menu_mall',
        'introductions' => ['channel_menu_mall_tip1', 'channel_menu_mall_tip2'],
        'icon' => '/images/product/mall_icon.png',
        'dotIcon' => '/images/product/mall_dot_icon.png',
        'keycode' => 'MALL', // default is 'PRODUCT'
        'type' => 'VIEW', // 'VIEW' 'CLICK'
        'msgType' => 'URL', // 'URL' / 'TEXT' or 'NEWS'
        'content' => DOMAIN . 'api/mobile/mall?appId={{appId}}&channelId={{channelId}}',
        'dataCallback' => ['\backend\modules\product\utils\Conf', 'getContentInfo'],
        'isEnabled' => true,
    ]/*, [
        'name' => 'product',
        'title' => 'channel_menu_campaign',
        'introductions' => ['channel_menu_campaign_tip1', 'channel_menu_campaign_tip2', 'channel_menu_campaign_tip3'],
        'icon' => '/images/product/campaign_icon.png',
        'dotIcon' => '/images/product/campaign_dot_icon.png',
        'keycode' => 'CAMPAGIN', // default is 'PRODUCT'
        'type' => 'VIEW', // 'VIEW' 'CLICK'
        'msgType' => 'URL', // 'URL' / 'TEXT' or 'NEWS'
        'content' => DOMAIN . 'api/mobile/campaign?appId={{appId}}&channelId={{channelId}}&campaignId=555ae287137473f6078b457c',
        'dataCallback' => ['\backend\modules\product\utils\Conf', 'getContentInfo'],
        'isEnabled' => true,
    ]*/
];
