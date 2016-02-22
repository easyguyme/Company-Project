<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\redis\Cache',
            'redis' => [
                'hostname' => CACHE_HOSTNAME,
                'port' => CACHE_PORT,
                'database' => CACHE_DB,
                'password' => CACHE_PASSWD,
            ]
        ],
        'resque'=> [
            'class' => 'backend\modules\resque\components\RResque',
            'server' => RESQUE_HOST,
            'port' => RESQUE_PORT,
            'database' => RESQUE_DB,
            'password' => RESQUE_PASSWD,
        ],
        'mongodb' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://'.MONGO_HOST.':'.MONGO_PORT.'/'.MONGO_DATABASE,
        ],
        'weConnect' => [
            'class' => '\backend\components\WeConnect',
            'weconnectDomain' => WECONNECT_DOMAIN,
            'wechatDomain' => 'https://api.weixin.qq.com'
        ],
        'extModule' => [
            'class' => 'common\components\ExtModule'
        ],
        'channelMenu' => [
            'class' => 'common\components\ChannelMenu'
        ],
        'job' => [
            'class' => '\backend\components\resque\Job',
        ],
        /*'jsSDK' => [
            'class' => '\backend\components\wechat\JsSDK',
            'appId' => WECAHT_JSSDK_APP_ID,
            'appSecret' => WECAHT_JSSDK_APP_SECRET,
            'domain' => 'https://api.weixin.qq.com/',
            'refererDomain' => DOMAIN
        ],*/
        'wechatSdk' => [
            'class'         => '\backend\components\wechat\WechatSdk',
            'domain'        => 'https://api.weixin.qq.com/',
            'refererDomain' => DOMAIN,
            'channelId'     => CHANNEL_ID
        ],
        'qiniu' => [
            'class' => '\backend\components\Qiniu',
            'bucket' => QINIU_BUCKET,
            'accessKey' => QINIU_ACCESS_KEY,
            'secretKey' => QINIU_SECRET_KEY,
            'domain' => QINIU_DOMAIN,
            'uploadDomain' => QINIU_UPLOAD_DOMAIN,
        ],
        'qrcode' => [
            'class' => '\backend\components\QrcodeService',
        ],
        'webhookEvent' => [
            'class' => '\backend\components\WebhookEventService',
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource'
                ],
            ],
        ],
        'service' => [
            'class' => 'backend\components\extservice\Service',
        ],
        'ddConnect' => [
            'class' => 'backend\components\dingding\Connect',
            'domain' => 'https://oapi.dingtalk.com',
        ],
        'ddJsSdk' => [
            'class' => 'backend\components\dingding\JsSdk',
        ],
    ],
    'timeZone' => 'Asia/Shanghai',
];
