<?php

use common\models\Payment;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Payment $model */

$this->title = 'To\'lov #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Payments'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="payment-view">

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

            // Type
            [
                'attribute' => 'type',
                'label'     => 'Tur',
                'format'    => 'raw',
                'value'     => match ((int)$model->type) {
                    Payment::TO_CARD  => '<span class="badge bg-blue-lt text-blue fs-6"><i class="ti ti-credit-card me-1"></i>Kartaga</span>',
                    Payment::TO_PHONE => '<span class="badge bg-cyan-lt text-cyan fs-6"><i class="ti ti-device-mobile me-1"></i>Telefonga</span>',
                    default           => '<span class="text-muted">—</span>',
                },
            ],

            'account',

            // Amount
            [
                'attribute' => 'amount',
                'label'     => 'Summa',
                'value'     => $model->amount ? number_format($model->amount, 0, '.', ' ') . " so'm" : '—',
            ],

            // Status
            [
                'attribute' => 'status',
                'label'     => 'Holat',
                'format'    => 'raw',
                'value'     => match ((int)$model->status) {
                    Payment::STATUS_SUCCESS => '<span class="badge bg-success-lt text-success fs-6"><i class="ti ti-circle-check me-1"></i>Muvaffaqiyatli</span>',
                    Payment::STATUS_CANCEL  => '<span class="badge bg-danger-lt text-danger fs-6"><i class="ti ti-ban me-1"></i>Bekor qilingan</span>',
                    default                 => '<span class="badge bg-warning-lt text-warning fs-6"><i class="ti ti-clock me-1"></i>Jarayonda</span>',
                },
            ],

            'created_at',
            'payment_id',
            'updated_at',
        ],
    ]) ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
