<?php

use common\models\Police;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Police $model */

$this->title = 'Polis #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Polices'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="police-view">

    <div class="d-flex gap-2 mb-3">
        <?= Html::a('<i class="ti ti-edit me-1"></i>' . Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="ti ti-trash me-1"></i>' . Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data'  => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method'  => 'post',
            ],
        ]) ?>
    </div>

    <?= DetailView::widget([
        'model'      => $model,
        'options'    => ['class' => 'table table-bordered detail-view'],
        'attributes' => [
            'id',
            [
                'attribute' => 'user_id',
                'format'    => 'raw',
                'value'     => $model->user_id
                    ? Html::a('<i class="ti ti-user me-1"></i>#' . $model->user_id, '#',
                        ['class' => 'user-info-link', 'data-user-id' => $model->user_id])
                    : '—',
            ],
            [
                'attribute' => 'provider_id',
                'value'     => Police::getProviderList()[$model->provider_id] ?? 'Unknown',
            ],
            'policeId',
            'anketa_id',
            'startAt',
            'endAt',
            'pdfUrl:url',

            // Payment status
            [
                'attribute' => 'payment_status',
                'label'     => "To'lov holati",
                'format'    => 'raw',
                'value'     => $model->payment_status == 1
                    ? '<span class="badge bg-success-lt text-success fs-6"><i class="ti ti-circle-check me-1"></i>To\'langan</span>'
                    : '<span class="badge bg-danger-lt text-danger fs-6"><i class="ti ti-circle-x me-1"></i>To\'lanmagan</span>',
            ],

            // Insurance status
            [
                'attribute' => 'status',
                'label'     => 'Sug\'urta holati',
                'format'    => 'raw',
                'value'     => $model->status == 1
                    ? '<span class="badge bg-green-lt text-green fs-6"><i class="ti ti-shield-check me-1"></i>Aktiv</span>'
                    : '<span class="badge bg-yellow-lt text-yellow fs-6"><i class="ti ti-clock me-1"></i>Kutilmoqda</span>',
            ],

            'paymentId',
            'paymentLink',
            'gateway',
            [
                'attribute' => 'amount',
                'label'     => 'Summa',
                'value'     => $model->amount ? number_format($model->amount, 0, '.', ' ') . " so'm" : '—',
            ],

            // Driver restriction
            [
                'attribute' => 'driverRestriction',
                'label'     => 'Haydovchi cheklovi',
                'format'    => 'raw',
                'value'     => $model->driverRestriction == 1
                    ? '<span class="badge bg-orange-lt text-orange fs-6"><i class="ti ti-lock me-1"></i>Cheklangan</span>'
                    : '<span class="badge bg-teal-lt text-teal fs-6"><i class="ti ti-lock-open me-1"></i>Cheklanmagan</span>',
            ],

            [
                'attribute' => 'season_id',
                'label'     => 'Mavsum',
                'format'    => 'raw',
                'value'     => $model->season
                    ? Html::a(
                        '<i class="ti ti-calendar me-1"></i>' . Html::encode($model->season->name),
                        Url::to(['/season/view', 'id' => $model->season_id]),
                        ['class' => 'text-decoration-none fw-medium']
                      )
                    : '<span class="text-secondary">—</span>',
            ],
            'created_at',
            'updated_at',
        ],
    ]) ?>

    <?php $vehicle = $model->vehicles[0] ?? null; ?>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title"><i class="ti ti-car me-1"></i>Avtomobil</h3></div>
        <div class="card-body">
            <?php if ($vehicle): ?>
                <table class="table table-sm mb-0">
                    <tr><th>Davlat raqami</th><td><?= Html::encode($vehicle->licenseNumber ?: '—') ?></td></tr>
                    <tr><th>Texpasport seriya</th><td><?= Html::encode($vehicle->techPassportSeria ?: '—') ?></td></tr>
                    <tr><th>Texpasport raqami</th><td><?= Html::encode($vehicle->techPassportNumber ?: '—') ?></td></tr>
                </table>
            <?php else: ?>
                <span class="text-secondary">—</span>
            <?php endif; ?>
        </div>
    </div>

    <?php $owner = $model->owners[0] ?? null; ?>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title"><i class="ti ti-id-badge-2 me-1"></i>Egasi</h3></div>
        <div class="card-body">
            <?php if ($owner): ?>
                <table class="table table-sm mb-0">
                    <?php if ($owner->type === 'ORGANIZATION'): ?>
                        <tr><th>Turi</th><td>Tashkilot</td></tr>
                        <tr><th>Nomi</th><td><?= Html::encode($owner->name ?: '—') ?></td></tr>
                        <tr><th>INN</th><td><?= Html::encode($owner->inn ?: '—') ?></td></tr>
                    <?php else: ?>
                        <tr><th>Turi</th><td>Jismoniy shaxs</td></tr>
                        <tr><th>F.I.Sh.</th><td><?= Html::encode(trim("{$owner->firstName} {$owner->middlename} {$owner->lastname}") ?: '—') ?></td></tr>
                        <tr><th>PINFL</th><td><?= Html::encode($owner->pinfl ?: '—') ?></td></tr>
                    <?php endif; ?>
                </table>
            <?php else: ?>
                <span class="text-secondary">—</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title"><i class="ti ti-users me-1"></i>Haydovchilar</h3></div>
        <div class="card-body">
            <?php if ($model->drivers): ?>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Ism</th>
                            <th>Passport</th>
                            <th>Tug'ilgan sana</th>
                            <th>Qarindoshligi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->drivers as $driver): ?>
                            <tr>
                                <td><?= Html::encode(trim("{$driver->firstName} {$driver->lastName}") ?: '—') ?></td>
                                <td><?= Html::encode(trim("{$driver->passportSeria} {$driver->passportNumber}") ?: '—') ?></td>
                                <td><?= Html::encode($driver->passportBirthdate ?: '—') ?></td>
                                <td><?= Html::encode($driver->relative->name ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <span class="text-secondary">—</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
