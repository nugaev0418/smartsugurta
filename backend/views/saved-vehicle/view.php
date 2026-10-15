<?php

use backend\ersp\ErspVehicleClient;
use common\models\SavedVehicle;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\SavedVehicle $model */

$this->title = "Avtomobil #{$model->id} ({$model->gov_number})";
$this->params['breadcrumbs'][] = ['label' => 'Mening avtolarim', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$statusLabels = SavedVehicle::statusLabels();
$statusClasses = [
    SavedVehicle::ERSP_STATUS_IDLE => 'bg-secondary-lt text-secondary',
    SavedVehicle::ERSP_STATUS_CHECKING => 'bg-info-lt text-info',
    SavedVehicle::ERSP_STATUS_DONE => 'bg-success-lt text-success',
    SavedVehicle::ERSP_STATUS_FAILED => 'bg-danger-lt text-danger',
];
$statusBadge = sprintf(
    '<span class="badge %s">%s</span>',
    $statusClasses[$model->ersp_check_status] ?? 'bg-secondary-lt text-secondary',
    Html::encode($statusLabels[$model->ersp_check_status] ?? $model->ersp_check_status)
);

$pretty = function (?string $json): string {
    if ($json === null || $json === '') {
        return '—';
    }
    $decoded = json_decode($json, true);
    $text = $decoded !== null
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $json;

    return '<pre class="mb-0" style="white-space: pre-wrap;">' . Html::encode($text) . '</pre>';
};

$allPolicies = $model->getCachedPolicies();
$today = new \DateTimeImmutable('today');
?>
<div class="saved-vehicle-view">

    <div class="mb-3 d-flex flex-wrap gap-2">
        <?= Html::a('<i class="ti ti-arrow-left me-1"></i>Ro\'yxatga qaytish', ['index'], ['class' => 'btn btn-secondary']) ?>

        <?php if ($model->canCheckNow()): ?>
            <?= Html::a('<i class="ti ti-refresh me-1"></i>Qayta tekshirish', ['recheck', 'id' => $model->id], [
                'class' => 'btn btn-info',
                'data' => ['method' => 'post', 'confirm' => 'ERSP orqali qayta tekshirilsinmi?'],
            ]) ?>
        <?php else: ?>
            <span class="btn btn-secondary disabled">
                <?php $minutes = (int) ceil($model->secondsUntilNextCheck() / 60); ?>
                <?= $model->ersp_check_status === SavedVehicle::ERSP_STATUS_CHECKING
                    ? 'Tekshirilmoqda...'
                    : "{$minutes} daqiqadan keyin qayta tekshirish mumkin" ?>
            </span>
        <?php endif; ?>

        <?= Html::a('<i class="ti ti-trash me-1"></i>O\'chirish', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => ['method' => 'post', 'confirm' => 'Rostdan o\'chirilsinmi? Bu amalni qaytarib bo\'lmaydi.'],
        ]) ?>
    </div>

    <?= DetailView::widget([
        'model'      => $model,
        'options'    => ['class' => 'table table-bordered detail-view'],
        'attributes' => [
            'id',
            [
                'attribute' => 'chat_id',
                'label'     => 'Foydalanuvchi',
                'format'    => 'raw',
                'value'     => $model->botuser
                    ? Html::a('<i class="ti ti-user me-1"></i>' . $model->botuser->chat_id, '#',
                        ['class' => 'user-info-link', 'data-chat-id' => $model->botuser->chat_id])
                    : '—',
            ],
            'gov_number',
            'tech_passport_seria',
            'tech_passport_number',
            'owner_type',
            'model',
            'vehicle_type_name',
            [
                'attribute' => 'ersp_check_status',
                'format'    => 'raw',
                'value'     => $statusBadge,
            ],
            'ersp_checked_at',
            'created_at',
            'updated_at',
        ],
    ]) ?>

    <h3 class="mt-4">Polisalar tarixi</h3>

    <?php if (!$allPolicies): ?>
        <p class="text-muted">Hali ERSP orqali tekshirilmagan yoki polisa topilmagan.</p>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach ($allPolicies as $policy):
                $endDate = ErspVehicleClient::endDateLabel($policy);
                $isActive = $endDate && new \DateTimeImmutable($endDate) >= $today;
                $company = $policy['Sug‘urta kompaniya'] ?? '—';
                $series = $policy['Polis seriyasi va raqami'] ?? '—';
                $remaining = ErspVehicleClient::remainingLabel($policy);
            ?>
                <div class="col">
                    <div class="card <?= $isActive ? 'border-success' : 'border-secondary' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="card-title mb-1"><?= Html::encode($company) ?></h4>
                                <span class="badge <?= $isActive ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' ?>">
                                    <?= $isActive ? 'Amalda' : 'Muddati tugagan' ?>
                                </span>
                            </div>
                            <p class="mb-1"><i class="ti ti-file-text me-1"></i><?= Html::encode($series) ?></p>
                            <p class="mb-1">
                                <i class="ti ti-calendar-event me-1"></i>
                                <?= $endDate ? date('d.m.Y', strtotime($endDate)) : "Noma'lum sana" ?>
                                <?= $remaining ? " ({$remaining})" : '' ?>
                            </p>
                            <?php if (!empty($policy['pdf_link'])): ?>
                                <?= Html::a('<i class="ti ti-file-download me-1"></i>PDF ko\'rish', $policy['pdf_link'], [
                                    'class' => 'btn btn-sm btn-outline-primary mt-2',
                                    'target' => '_blank',
                                    'rel' => 'noopener',
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h4 class="mt-4">Xom JSON</h4>
    <?= $pretty($model->ersp_policies_json) ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
