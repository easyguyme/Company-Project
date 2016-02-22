<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

require(__DIR__ . '/../../common/config/modules.php');

return [
    'id' => 'app-webapp',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'modules' => initModules('webapp'),
    'components' => [
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'webapp/common/pay/<module:[\w\/-]+>' => '<module>/<module>/pay',
                'webapp/<module:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<id:[\w\d,]{24}(,[\w\d]{24})*>'       => '<module>/<controller>/<action>',
                'webapp/<module:\w+>/<submodule:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<id:[\w\d,]{24}>'     => '<module>/<submodule>/<controller>/<action>',
                'webapp/<module:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<page:[\w\/-]+>'                                      => '<module>/<controller>/<action>',
                'webapp/<module:\w+>/<submodule:\w+>/<controller:[\w-]+>/<action:[\w-]+>/<page:[\w\/-]+>'                      => '<module>/<submodule>/<controller>/<action>',
                'webapp/<module:\w+>/<controller:[\w-]+>/<action:[\w-]+>'                                      => '<module>/<controller>/<action>',
                'webapp/<module:\w+>/<submodule:\w+>/<controller:[\w-]+>/<action:[\w-]+>'                      => '<module>/<submodule>/<controller>/<action>',
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
