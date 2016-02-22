<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

require(__DIR__ . '/../../common/config/modules.php');

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => initModules('backend'),
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'backend\utils\WMLogTarget',
                    'levels' => ['error', 'warning'],
                    'exportInterval' => 1,
                ],
            ],
        ],
        /*'errorHandler' => [
            'class' => 'site/error',
        ],*/
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'api/mobile/check-bind'                                                     => 'mobile/check-bind',
                'api/mobile/check-bind/<type>/<param:.*>'                                   => 'mobile/check-bind',
                'api/mobile/user-info'                                                      => 'mobile/user-info',
                'api/mobile/user-info/<type>/<param:.*>'                                    => 'mobile/user-info',
                'api/mobile/openid'                                                         => 'mobile/openid',
                'api/mobile/openid/<type>/<param:.*>'                                       => 'mobile/openid',
                'api/mobile/check-pay/<type>/<param:.*>'                                    => 'mobile/check-pay',

                'POST api/<controller:[\w-]+>s'                                             => '<controller>/create',
                'api/<controller:[\w-]+>s'                                                  => '<controller>/index',
                'PUT api/<controller:[\w-]+>/<id:[\w\d,]{24}>'                              => '<controller>/update',
                'DELETE api/<controller:[\w-]+>/<id:[\w\d]{24}(,[\w\d]{24})*>'              => '<controller>/delete',
                'api/<controller:[\w-]+>/<id:[\w\d,]{24}>'                                  => '<controller>/view',

                'POST api/<module:\w+>/<controller:[\w-]+>s'                                => '<module>/<controller>/create',
                'api/<module:\w+>/<controller:[\w-]+>s'                                     => '<module>/<controller>/index',
                'PUT api/<module:\w+>/<controller:[\w-]+>/<id:[\w\d,]{24}>'                 => '<module>/<controller>/update',
                'DELETE api/<module:\w+>/<controller:[\w-]+>/<id:[\w\d]{24}(,[\w\d]{24})*>' => '<module>/<controller>/delete',
                'api/<module:\w+>/<controller:[\w-]+>/<id:[\w\d,]{24}>'                     => '<module>/<controller>/view',

                'api/<controller:[\w-]+>/<action:[\w-]+>/<id:[\w\d,]{24}>'                                  => '<controller>/<action>',
                'api/<module:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<id:[\w\d,]{24}(,[\w\d]{24})*>'       => '<module>/<controller>/<action>',
                'api/<module:\w+>/<submodule:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<id:[\w\d,]{24}>'     => '<module>/<submodule>/<controller>/<action>',
                'api/<controller:[\w-]+>/<action:[\w-]+>'                                                   => '<controller>/<action>',
                'api/<module:\w+>/<controller:[\w-]+>/<action:[\w-]+>'                                      => '<module>/<controller>/<action>',
                'api/<module:\w+>/<submodule:\w+>/<controller:[\w-]+>/<action:[\w-]+>'                      => '<module>/<submodule>/<controller>/<action>',
            ],
        ],
        'urlService' => [
            'class' => '\backend\components\UrlService',
            'shortUrlDomain' => SHORT_URL_DOMAIN
        ],
        'staticPageService' => [
            'class' => '\backend\components\page\StaticPageService',
        ],
        'mail' => [
            'class' => '\backend\components\mail\Mailer',
            'api_user' => SENDCLOUD_API_USER,
            'api_key' => SENDCLOUD_API_KEY,
            'from' => SENDCLOUD_FROM,
            'fromname' => SENDCLOUD_FROM_NAME,
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'curl' => [
            'class' => '\backend\components\Curl',
            'options' => [
                CURLOPT_MAXREDIRS => 1,
            ],
        ],
        'file' => [
            'class' => '\backend\components\File'
        ],
        'tuisongbao' => [
            'class' => 'backend\components\Tuisongbao',
            'domain' => TUISONGBAO_DOMAIN,
            'appId' => TUISONGBAO_APP_ID,
            'secret' => TUISONGBAO_SECRET,
            'pushAppId' => TUISONGBAO_PUSH_APP_ID,
            'pushSecret' => TUISONGBAO_PUSH_SECRET
        ],
        'weiboConnect' => [
            'class' => '\backend\components\WeiboConnect',
            'appKey' => WEIBO_APP_KEY,
            'appSecret' => WEIBO_APP_SECRET,
            'redirectUri' => WEIBO_REDIRECT_URI,
            'sinaOauthDomain' => 'https://api.weibo.com/oauth2',
        ],
        'webhook' => [
            'class' => '\backend\components\Webhook',
            'domain' => WEBHOOK_DOMAIN
        ],
        'tradeService'   => [
            'class' => 'backend\components\TradeService',
            'weconnectDomain' => WECONNECT_DOMAIN,
        ],
    ],
    'params' => $params,
];
