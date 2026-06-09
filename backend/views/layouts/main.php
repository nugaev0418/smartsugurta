<?php

/** @var \yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use common\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);

$currentController = Yii::$app->controller ? Yii::$app->controller->id : '';
$currentAction     = Yii::$app->controller && Yii::$app->controller->action ? Yii::$app->controller->action->id : '';

$navItems = [
    ['label' => 'Home',     'url' => '/site/index',     'icon' => 'ti-home',           'controller' => 'site',     'action' => 'index'],
    ['label' => 'Vehicle',  'url' => '/vehicle/index',  'icon' => 'ti-car',            'controller' => 'vehicle'],
    ['label' => 'User',     'url' => '/botuser/index',  'icon' => 'ti-users',          'controller' => 'botuser'],
    ['label' => 'History',  'url' => '/history/index',  'icon' => 'ti-history',        'controller' => 'history'],
    ['label' => 'Driver',   'url' => '/driver/index',   'icon' => 'ti-steering-wheel', 'controller' => 'driver'],
    ['label' => 'Owner',    'url' => '/owner/index',    'icon' => 'ti-user-check',     'controller' => 'owner'],
    ['label' => 'Police',   'url' => '/police/index',   'icon' => 'ti-shield',         'controller' => 'police'],
    ['label' => 'Relative', 'url' => '/relative/index', 'icon' => 'ti-users-group',    'controller' => 'relative'],
    ['label' => 'Season',   'url' => '/season/index',   'icon' => 'ti-calendar',       'controller' => 'season'],
    ['label' => 'Payment',  'url' => '/payment/index',  'icon' => 'ti-credit-card',    'controller' => 'payment'],
    ['label' => 'Text',     'url' => '/text/index',     'icon' => 'ti-file-text',      'controller' => 'text'],
    ['label' => 'Broadcast', 'url' => '/broadcast/index', 'icon' => 'ti-speakerphone',   'controller' => 'broadcast'],
    ['label' => 'Income',   'url' => '/income/index',   'icon' => 'ti-cash',           'controller' => 'income'],
    ['label' => 'Setting',  'url' => '/setting/index',  'icon' => 'ti-adjustments',    'controller' => 'setting'],
];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="antialiased">
<?php $this->beginBody() ?>

<div class="wrapper">

    <!-- Vertical sidebar -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                    aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="<?= Yii::$app->homeUrl ?>" class="text-white text-decoration-none fw-bold fs-3">
                    <?= Html::encode(Yii::$app->name) ?>
                </a>
            </h1>

            <!-- Mobile user icon -->
            <div class="navbar-nav flex-row d-lg-none">
                <?php if (!Yii::$app->user->isGuest): ?>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0"
                       data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm">
                            <i class="ti ti-user fs-4 text-white"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <div class="dropdown-header"><?= Html::encode(Yii::$app->user->identity->username) ?></div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= Url::to(['/site/settings']) ?>" class="dropdown-item">
                            <i class="dropdown-item-icon ti ti-settings"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <?= Html::beginForm(['/site/logout'], 'post') ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="dropdown-item-icon ti ti-logout"></i> Logout
                        </button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($navItems as $item):
                        if (isset($item['action'])) {
                            $isActive = $currentController === $item['controller'] && $currentAction === $item['action'];
                        } else {
                            $isActive = $currentController === $item['controller'];
                        }
                    ?>
                    <li class="nav-item<?= $isActive ? ' active' : '' ?>">
                        <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= Url::to([$item['url']]) ?>">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti <?= $item['icon'] ?>"></i>
                            </span>
                            <span class="nav-link-title"><?= $item['label'] ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main content wrapper -->
    <div class="page-wrapper">

        <!-- Top navbar -->
        <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
            <div class="container-fluid">
                <div class="d-flex flex-fill align-items-center">
                    <?php if (!empty($this->params['breadcrumbs'])): ?>
                    <ol class="breadcrumb mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item">
                            <a href="<?= Yii::$app->homeUrl ?>">Home</a>
                        </li>
                        <?php foreach ($this->params['breadcrumbs'] as $breadcrumb): ?>
                            <?php if (is_array($breadcrumb)): ?>
                            <li class="breadcrumb-item">
                                <?= Html::a(Html::encode($breadcrumb['label']), $breadcrumb['url']) ?>
                            </li>
                            <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?= Html::encode($breadcrumb) ?>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>
                </div>

                <div class="navbar-nav flex-row order-md-last">
                    <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0"
                           data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm rounded-circle bg-primary-lt">
                                <i class="ti ti-user"></i>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="fw-medium lh-1"><?= Html::encode(Yii::$app->user->identity->username) ?></div>
                                <div class="mt-1 small text-secondary">Administrator</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?= Url::to(['/site/settings']) ?>" class="dropdown-item">
                                <i class="dropdown-item-icon ti ti-settings"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <?= Html::beginForm(['/site/logout'], 'post') ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="dropdown-item-icon ti ti-logout"></i> Logout
                            </button>
                            <?= Html::endForm() ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="nav-item">
                        <a href="<?= Url::to(['/site/login']) ?>" class="nav-link">
                            <i class="ti ti-login me-1"></i> Login
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Page body -->
        <div class="page-body">
            <div class="container-fluid">

                <?php if (!empty($this->params['breadcrumbs']) || isset($this->title)): ?>
                <div class="page-header d-print-none mb-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title"><?= Html::encode($this->title ?? '') ?></h2>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?= Alert::widget() ?>
                <?= $content ?>

            </div>
        </div>

        <footer class="footer footer-transparent d-print-none">
            <div class="container-fluid">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-lg-auto ms-lg-auto">
                        <span class="text-secondary small">
                            &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>
                        </span>
                    </div>
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <span class="text-secondary small"><?= Yii::powered() ?></span>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
