<?php

return [
    'tab'=>[
        'tabs'=>[
            [
                'name'=>'标题一',
                'active'=>true
            ],
            [
                'name'=>'标题二',
                'active'=>false
            ]
        ],
        'style'=>'1'
    ],
    'delimiter'=>[
        'style'=>'wm-delimiter-solid'
    ],
    'title'=>[
        'name'=>'',
        'style'=>'plain',
        'link'=>''
    ],
    'pic'=>[
        'name'=>'图片名称',
        'imageUrl'=>'',
        'linkUrl' =>''
    ],
    'table'=>[
        'content'=>'<table>
                        <tbody>
                        <tr class="firstRow">
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容1</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容2</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容3</td>
                        </tr>
                        <tr>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容4</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容5</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容6</td>
                        </tr>
                        <tr>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容7</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容8</td>
                            <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容9</td>
                        </tr>
                        </tbody>
                    </table>'
    ],
    'sms'=>[
        'tel'=>'',
        'smsText'=>''
    ],
    'html'=>[
        'content'=>''
    ],
    'contact'=>[
        'name'=>'',
        'tel'=>'',
        'email'=>'',
        'qq'=>'',
        'location'=>[
            'province'=>'',
            'city'=>'',
            'county'=>'',
        ]
    ],
    'map'=>[
        'name'=>'',
        'location'=>[
            'province'=>'',
            'city'=>'',
            'county'=>'',
        ],
        'town'=>'',
        'address'=>'',
        'url'=>'http://api.map.baidu.com/staticimage?center=121.595105,31.198901&width=300&height=260&zoom=14&markers=121.595105,31.198901&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1',
        'isDisplayMapIcon' => 'true'
    ],
    'cover1'=>[
        'slideInfo'=>[
            [
                'name'=>'每日推荐',
                'pic'=>'/images/microsite/coverpageone_picture.png',
                'linkUrl'=>'',
                'defaultPic'=>'/images/microsite/coverpageone_picture.png',
            ],
            [
                'name'=>'默认幻灯片图片',
                'pic'=>'/images/microsite/defaultimage.png',
                'linkUrl'=>'',
                'defaultPic'=>'/images/microsite/defaultimage.png',
            ],
        ],
        'setting'=>'3000',
        'navInfo' => [
            [
                'name' => '地图导航',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_sitmaps.png'
            ],
            [
                'name' => '热门景区',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_hotspots.png'
            ],
            [
                'name' => '在线沟通',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_onlinecommunication.png'
            ],
            [
                'name' => '购票指南',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_guidetobuyingtickets.png'
            ],
            [
                'name' => '特价机票',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_specialtickets.png'
            ],
            [
                'name' => '我的订单',
                'linkUrl' => '',
                'iconUrl' => '/images/microsite/coverpageone_myorder.png'
            ],
        ]
    ],
    'cover2'=>[
        'imageUrl'=>'',
        'linkUrl' =>'',
        'navInfo' => [
            [
                'name' => '正在热映',
                'linkUrl' => ''
            ],
            [
                'name' => '即将上映',
                'linkUrl' => ''
            ],
            [
                'name' => '最热排行',
                'linkUrl' => ''
            ],
            [
                'name' => '评分最高',
                'linkUrl' => ''
            ]
        ]
    ],

    'slide'=>[
            'info'=>[
                [
                    'name'=>'图片名称',
                    'pic'=>'',
                    'linkUrl'=>'',
                    'defaultPic'=>'/images/content/default.png',
                ],
                [
                    'name'=>'图片名称',
                    'pic'=>'',
                    'linkUrl'=>'',
                    'defaultPic'=>'/images/content/default.png',
                ],
             ],
            'setting'=>'0'
     ],
    'text'=>[
        'text'=>'',
        'setting'=>'full'
    ],
    'messages'=>[
        'title'=>'',
        'message_board_id'=>'',
        'info'=>[
            [
                'question'=>'',
                'textType'=>'single',
                'required'=>'true'
            ]
        ]
    ],
    'tel'=>[
        'tel'=>'',
        'tag'=>'',
        'style'=>'1'
    ],
    'share'=>[
        'content'=>''
    ],
    'cover3'=>[
        'navs'=>[
            [
                'name'=>'时尚资讯',
                'pic'=>'',
                'icon' => '',
                'linkUrl'=>'',
            ],
         ],
    ],
    'nav' => [
        'infos' => [
            [
                'name' => '默认页',
                'linkUrl' => '',
            ],
            [
                'name' => '默认页',
                'linkUrl' => '',
            ],
        ],
        'isFirstTime' => 'true',
    ],
    'album' => [
        'defaultTitle' => '图集标题',
        'title' => '',
        'album' => [['defaultUrl' => '/images/content/default.png', 'description' => '', 'url' => ''],
                         ['defaultUrl' => '/images/content/default.png', 'description' => '', 'url' => ''],
                         ['defaultUrl' => '/images/content/default.png', 'description' => '', 'url' => ''],],
        'column' => '3',
    ],
    'articles' => [
        'title' => '群脉微网站文章使用帮助',
        'image' => '/images/content/default.png',
        'content' => '<p>如何使用群脉微网站功能，拖拽一个组件，调节样式，点击保存，同时，可以预览生成的页面</p>',
        'fields' => [],
        'style' => 1
    ],
    'link' => [
        'defaultName' => '未添加链接',
        'name' => '',
        'linkUrl' => '',
        'display' => 'left',
    ],
    'lottery'=>[],
    'questionnaire'=>[
        'style' => '1',
        'questionnaireId' => '',
    ],
    'coupon'=>[
        'title' => '优惠券名称',
        'image' => '/images/content/conf/webmaterial_article_defaultpicture.png',
        'url' => '#',
        'couponId' => '',
        'channelId' => '',
        'style' => '1'
    ]
];
