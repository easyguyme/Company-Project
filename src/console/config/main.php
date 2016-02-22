<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

require(__DIR__ . '/../../common/config/modules.php');

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'modules' => initModules('console'),
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                ],
            ],
        ],
        'urlService' => [
            'class' => '\backend\components\UrlService',
            'shortUrlDomain' => SHORT_URL_DOMAIN
        ],
        'curl' => [
            'class' => '\backend\components\Curl',
            'options' => [
                CURLOPT_MAXREDIRS => 1,
            ],
        ],
        'tuisongbao' => [
            'class' => 'backend\components\Tuisongbao',
            'domain' => TUISONGBAO_DOMAIN,
            'appId' => TUISONGBAO_APP_ID,
            'secret' => TUISONGBAO_SECRET,
            'pushAppId' => TUISONGBAO_PUSH_APP_ID,
            'pushSecret' => TUISONGBAO_PUSH_SECRET
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@backend/messages'
                ]
            ]
        ]
    ],
    'params' => $params,
];
