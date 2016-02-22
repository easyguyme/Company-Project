<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlService' => [
            'class' => '\backend\components\UrlService',
            'shortUrlDomain' => SHORT_URL_DOMAIN
        ],
        /*'errorHandler' => [
            'errorAction' => 'site/error',
        ],*/
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/landing',
                'site/<action:[\w\/-]+>' => 'site/index',
                'chat/<action:[\w\/-]+>' => 'site/chat',
                'msite/<action:[\w\/-]+>/<id:[\w\d]{24}>' => 'msite/<action>',
                'msite/<action:[\w\/-]+>' => 'msite/<action>',
                'mobile/<action:[\w\/-]+>/<page:[\w\/-]+>' => 'mobile/<action>',
                'content/<action:[\w-]+>/<id:[\w\d]{24}>' => 'content/<action>',
                'map/<action:[\w-]+>' => 'map/<action>',
                'faq' => 'faq/index',
                '<controller:\w+>/<action:[\w\/-]+>' => 'site/index',
            ],
        ],
        'curl' => [
            'class' => '\backend\components\Curl',
            'options' => [
                CURLOPT_MAXREDIRS => 1,
            ],
        ],
    ],
    'params' => $params,
];
