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
            'user_id',
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

</div>
