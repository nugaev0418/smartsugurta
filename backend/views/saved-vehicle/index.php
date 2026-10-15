<?php

use backend\ersp\ErspVehicleClient;
use common\models\SavedVehicle;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\SavedVehicleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = "Mening avtolarim";
$this->params['breadcrumbs'][] = $this->title;

$statusBadge = function (string $status): string {
    $labels = SavedVehicle::statusLabels();
    $classes = [
        SavedVehicle::ERSP_STATUS_IDLE => 'bg-secondary-lt text-secondary',
        SavedVehicle::ERSP_STATUS_CHECKING => 'bg-info-lt text-info',
        SavedVehicle::ERSP_STATUS_DONE => 'bg-success-lt text-success',
        SavedVehicle::ERSP_STATUS_FAILED => 'bg-danger-lt text-danger',
    ];

    return sprintf(
        '<span class="badge %s">%s</span>',
        $classes[$status] ?? 'bg-secondary-lt text-secondary',
        Html::encode($labels[$status] ?? $status)
    );
};

$activePoliciesBadge = function (SavedVehicle $model): string {
    $active = ErspVehicleClient::filterActivePolicies($model->getCachedPolicies());

    if (!$active) {
        return '<span class="text-muted">Yo\'q</span>';
    }

    $soonest = null;
    foreach ($active as $policy) {
        $days = ErspVehicleClient::daysRemaining($policy);
        if ($days !== null && ($soonest === null || $days < $soonest)) {
            $soonest = $days;
        }
    }

    $color = $soonest === null ? 'secondary' : ($soonest <= 7 ? 'danger' : ($soonest <= 30 ? 'warning' : 'success'));

    return sprintf(
        '<span class="badge bg-%s-lt text-%s">%d ta%s</span>',
        $color,
        $color,
        count($active),
        $soonest !== null ? ", {$soonest} kun qoldi" : ''
    );
};
?>
<div class="saved-vehicle-index">

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'chat_id',
                'label'     => 'Foydalanuvchi',
                'format'    => 'raw',
                'value'     => fn(SavedVehicle $model) => $model->botuser
                    ? Html::a('<i class="ti ti-user me-1"></i>' . $model->botuser->chat_id, '#',
                        ['class' => 'user-info-link text-decoration-none', 'data-chat-id' => $model->botuser->chat_id])
                    : '—',
            ],
            'gov_number',
            [
                'label'  => 'Avtomobil',
                'format' => 'raw',
                'value'  => function (SavedVehicle $model) {
                    $line = Html::encode(trim("{$model->model} {$model->vehicle_type_name}")) ?: '—';
                    $owner = $model->owner_type ? '<span class="badge bg-secondary-lt text-secondary ms-1">' . Html::encode($model->owner_type) . '</span>' : '';
                    return $line . $owner;
                },
            ],
            [
                'attribute' => 'ersp_check_status',
                'label'     => 'Tekshiruv holati',
                'format'    => 'raw',
                'filter'    => SavedVehicle::statusLabels(),
                'value'     => fn(SavedVehicle $model) => $statusBadge($model->ersp_check_status),
            ],
            [
                'label'  => 'Amaldagi polisalar',
                'format' => 'raw',
                'value'  => fn(SavedVehicle $model) => $activePoliciesBadge($model),
            ],
            'ersp_checked_at',
            'created_at',
            [
                'class'      => ActionColumn::class,
                'template'   => '{view} {recheck} {delete}',
                'urlCreator' => fn($action, $model) => Url::toRoute([$action, 'id' => $model->id]),
                'buttons' => [
                    'recheck' => fn($url, SavedVehicle $model) => $model->canCheckNow() ? Html::a('<i class="ti ti-refresh"></i>', $url, [
                        'title' => 'Qayta tekshirish',
                        'data'  => ['method' => 'post', 'confirm' => 'ERSP orqali qayta tekshirilsinmi?'],
                    ]) : '',
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
