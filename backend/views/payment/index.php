<?php

use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\PaymentSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Payments');
$this->params['breadcrumbs'][] = $this->title;

$typeList   = [Payment::TO_CARD => 'Kartaga', Payment::TO_PHONE => 'Telefonga'];
$statusList = [
    Payment::STATUS_PROCESS => 'Jarayonda',
    Payment::STATUS_SUCCESS => 'Muvaffaqiyatli',
    Payment::STATUS_CANCEL  => 'Bekor qilingan',
];
?>
<div class="payment-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>' . Yii::t('app', 'Create Payment'), ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'user_id',
                'format'    => 'raw',
                'value'     => fn($model) => $model->user_id
                    ? Html::a('<i class="ti ti-user me-1"></i>' . $model->user_id, '#',
                        ['class' => 'user-info-link text-decoration-none', 'data-user-id' => $model->user_id])
                    : '—',
            ],

            // Type
            [
                'attribute' => 'type',
                'label'     => 'Tur',
                'format'    => 'raw',
                'filter'    => $typeList,
                'value'     => function ($model) use ($typeList) {
                    if ($model->type == Payment::TO_CARD) {
                        return '<span class="badge bg-blue-lt text-blue"><i class="ti ti-credit-card me-1"></i>Kartaga</span>';
                    }
                    if ($model->type == Payment::TO_PHONE) {
                        return '<span class="badge bg-cyan-lt text-cyan"><i class="ti ti-device-mobile me-1"></i>Telefonga</span>';
                    }
                    return '<span class="text-muted">—</span>';
                },
            ],

            'account',

            // Amount
            [
                'attribute' => 'amount',
                'label'     => 'Summa',
                'format'    => 'raw',
                'value'     => fn($model) => $model->amount
                    ? '<span class="fw-medium">' . number_format($model->amount, 0, '.', ' ') . " so'm</span>"
                    : '—',
            ],

            // Status
            [
                'attribute' => 'status',
                'label'     => 'Holat',
                'format'    => 'raw',
                'filter'    => $statusList,
                'value'     => function ($model) {
                    return match ((int)$model->status) {
                        Payment::STATUS_SUCCESS => '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Muvaffaqiyatli</span>',
                        Payment::STATUS_CANCEL  => '<span class="badge bg-danger-lt text-danger"><i class="ti ti-ban me-1"></i>Bekor qilingan</span>',
                        default                 => '<span class="badge bg-warning-lt text-warning"><i class="ti ti-clock me-1"></i>Jarayonda</span>',
                    };
                },
            ],

            'created_at',

            [
                'class'      => ActionColumn::class,
                'urlCreator' => fn($action, Payment $model) => Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
    ]); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
