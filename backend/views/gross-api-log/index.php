<?php

use common\models\GrossApiLog;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\GrossApiLogSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Gross API loglari';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gross-api-log-index">

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'chat_id',
                'format'    => 'raw',
                'value'     => fn($model) => $model->chat_id
                    ? Html::a('<i class="ti ti-user me-1"></i>' . $model->chat_id, '#',
                        ['class' => 'user-info-link text-decoration-none', 'data-chat-id' => $model->chat_id])
                    : '—',
            ],
            'run_id',
            'label',
            'attempt',
            [
                'attribute' => 'success',
                'format'    => 'raw',
                'value'     => fn($model) => $model->success
                    ? '<span class="badge bg-success">OK</span>'
                    : '<span class="badge bg-danger">Xato</span>',
            ],
            'error_message:ntext',
            'police_id',
            'created_at',
            [
                'class'      => ActionColumn::class,
                'template'   => '{view}',
                'urlCreator' => fn($action, GrossApiLog $model) => Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
