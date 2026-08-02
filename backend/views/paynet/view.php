<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Paynet $model */

$this->title = $model->name ?: ('Paynet #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Paynet tokenlar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paynet-view">

    <div class="d-flex gap-2 mb-3">
        <?php if ($model->is_active): ?>
            <?= Html::a('<i class="ti ti-toggle-left me-1"></i>Faolsizlantirish', ['deactivate', 'id' => $model->id], [
                'class' => 'btn btn-secondary',
                'data'  => ['confirm' => 'Ushbu tokenni faolsizlantirmoqchimisiz?', 'method' => 'post'],
            ]) ?>
        <?php else: ?>
            <?= Html::a('<i class="ti ti-toggle-right me-1"></i>Faollashtirish', ['activate', 'id' => $model->id], [
                'class' => 'btn btn-success',
                'data'  => ['confirm' => 'Ushbu tokenni faollashtirmoqchimisiz?', 'method' => 'post'],
            ]) ?>
        <?php endif; ?>
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
            'name',
            'paynet_id',
            'api_token',
            [
                'attribute' => 'is_active',
                'label'     => 'Holat',
                'format'    => 'raw',
                'value'     => $model->is_active
                    ? '<span class="badge bg-success-lt text-success fs-6"><i class="ti ti-circle-check me-1"></i>Faol</span>'
                    : '<span class="badge bg-secondary-lt text-secondary fs-6"><i class="ti ti-circle-x me-1"></i>Nofaol</span>',
            ],
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
