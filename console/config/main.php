<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'paynetQueue',
        'grossQueue',
        'broadcastQueue',
    ],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => \yii\console\controllers\FixtureController::class,
            'namespace' => 'common\fixtures',
          ],
    ],
    'components' => [
        'telegram' => [
            'class' => '\common\eleirbag\Telegram',
            'bot_token' => getenv('TELEGRAM_BOT_TOKEN'),
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager', // or use
        ],
        'mutex' => \yii\mutex\MysqlMutex::class,
        'paynetQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'paynet',
        ],
        'grossQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'gross',
        ],
        'broadcastQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'broadcast',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
    ],
    'params' => $params,
];
