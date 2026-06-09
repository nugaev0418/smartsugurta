<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => 'Smart Panel',
    'container' => [
        'definitions' => [
            \yii\grid\GridView::class => [
                'layout'         => "{summary}\n{items}\n{pager}",
                'summaryOptions' => ['class' => 'text-muted small mb-2'],
                'tableOptions'   => ['class' => 'table table-vcenter card-table'],
                'pager'          => [
                    'class'          => \yii\bootstrap5\LinkPager::class,
                    'prevPageLabel'  => '<i class="ti ti-chevron-left"></i>',
                    'nextPageLabel'  => '<i class="ti ti-chevron-right"></i>',
                    'firstPageLabel' => '<i class="ti ti-chevrons-left"></i>',
                    'lastPageLabel'  => '<i class="ti ti-chevrons-right"></i>',
                    'options'        => ['class' => 'pagination mb-0 mt-3'],
                ],
            ],
        ],
    ],
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => [
        'log',
        'paynetQueue',
        'grossQueue',
    ],
    'modules' => [
        'sarmin' => [
            'class' => 'mdm\admin\Module',
            'layout' => 'left-menu',
            'mainLayout' => '@app/views/layouts/main.php',
        ]
    ],
    'components' => [
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => ['css' => [], 'js' => []],
                'yii\bootstrap5\BootstrapPluginAsset' => ['css' => [], 'js' => [], 'depends' => []],
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager', // or use
        ],
        'mutex' => \yii\mutex\MysqlMutex::class,
        'paynetQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'paynet',
            'mutexTimeout' => 60
        ],
        'grossQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'gross',
            'mutexTimeout' => 60
        ],
        'broadcastQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'channel' => 'broadcast',
            'mutexTimeout' => 60
        ],
        'telegram' => [
            'class' => '\common\eleirbag\Telegram',
            'bot_token' => getenv('TELEGRAM_BOT_TOKEN'),
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],

    ],
    'params' => $params,
    'as access' => [
        'class' => 'mdm\admin\components\AccessControl',
        'allowActions' => [
            '*',
            'site/*',
            'police/check',
            'police/gross',
            'bot/start'
            //'sarmin/*'
        ]
    ],
];
