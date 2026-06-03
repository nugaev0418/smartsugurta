<?php

use common\models\Police;
use common\models\SeasonalInsurance;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\PoliceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Polices');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="police-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>' . Yii::t('app', 'Create Police'), ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'user_id',
            [
                'attribute' => 'provider_id',
                'value'     => fn($model) => Police::getProviderList()[$model->provider_id] ?? 'Unknown',
                'filter'    => Police::getProviderList(),
            ],
            'policeId',
            'anketa_id',
            'startAt',

            // Payment status — To'langan / To'lanmagan
            [
                'attribute' => 'payment_status',
                'label'     => "To'lov",
                'format'    => 'raw',
                'filter'    => [0 => "To'lanmagan", 1 => "To'langan"],
                'value'     => function ($model) {
                    if ($model->payment_status == 1) {
                        return '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>To\'langan</span>';
                    }
                    return '<span class="badge bg-danger-lt text-danger"><i class="ti ti-circle-x me-1"></i>To\'lanmagan</span>';
                },
            ],

            // Insurance status — Aktiv / Nofaol
            [
                'attribute' => 'status',
                'label'     => 'Status',
                'format'    => 'raw',
                'filter'    => [0 => 'Nofaol', 1 => 'Aktiv'],
                'value'     => function ($model) {
                    if ($model->status == 1) {
                        return '<span class="badge bg-green-lt text-green"><i class="ti ti-shield-check me-1"></i>Aktiv</span>';
                    }
                    return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-clock me-1"></i>Kutilmoqda</span>';
                },
            ],

            'gateway',
            [
                'attribute' => 'amount',
                'label'     => 'Summa',
                'format'    => 'raw',
                'value'     => fn($model) => $model->amount
                    ? '<span class="fw-medium">' . number_format($model->amount, 0, '.', ' ') . ' so\'m</span>'
                    : '—',
            ],

            // Driver restriction — Cheklangan / Cheklanmagan
            [
                'attribute' => 'driverRestriction',
                'label'     => 'Cheklov',
                'format'    => 'raw',
                'filter'    => [0 => 'Cheklanmagan', 1 => 'Cheklangan'],
                'value'     => function ($model) {
                    if ($model->driverRestriction == 1) {
                        return '<span class="badge bg-orange-lt text-orange"><i class="ti ti-lock me-1"></i>Cheklangan</span>';
                    }
                    return '<span class="badge bg-teal-lt text-teal"><i class="ti ti-lock-open me-1"></i>Cheklanmagan</span>';
                },
            ],

            // Season name
            [
                'attribute' => 'season_id',
                'label'     => 'Mavsum',
                'value'     => fn($model) => $model->season->name ?? '—',
                'filter'    => ArrayHelper::map(SeasonalInsurance::find()->all(), 'id', 'name'),
            ],

            'created_at',

            [
                'class'      => ActionColumn::class,
                'urlCreator' => function ($action, Police $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                },
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
