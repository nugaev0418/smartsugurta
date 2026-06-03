<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\History $model */

$this->title = 'Tarix #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Histories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="history-view">

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
                'attribute' => 'chat_id',
                'format'    => 'raw',
                'value'     => $model->chat_id
                    ? Html::a('<i class="ti ti-user me-1"></i>' . $model->chat_id, '#',
                        ['class' => 'user-info-link', 'data-chat-id' => $model->chat_id])
                    : '—',
            ],
            'message:ntext',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
