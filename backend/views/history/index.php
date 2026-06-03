<?php

use common\models\History;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\HistorySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Histories');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="history-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>' . Yii::t('app', 'Create History'), ['create'], ['class' => 'btn btn-success']) ?>
    </div>

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
            'message:ntext',
            'created_at',
            'updated_at',
            [
                'class'      => ActionColumn::class,
                'urlCreator' => fn($action, History $model) => Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
