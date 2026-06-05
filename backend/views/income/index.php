<?php

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\IncomeSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Daromadlar';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="income-index">

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'showFooter'   => true,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',

            [
                'attribute' => 'user_id',
                'label'     => 'Foydalanuvchi',
                'format'    => 'raw',
                'value'     => function ($model) {
                    $user = $model->user;
                    if (!$user) return '—';
                    $name = trim("{$user->fname} {$user->lname}") ?: "ID: {$user->id}";
                    return Html::a('<i class="ti ti-user me-1"></i>' . Html::encode($name), '#',
                        ['class' => 'user-info-link text-decoration-none', 'data-user-id' => $user->id]);
                },
            ],

            [
                'attribute'     => 'amount',
                'label'         => 'Miqdor',
                'format'        => 'raw',
                'footer'        => '<strong>' . number_format(
                    (clone $dataProvider->query)->sum('amount') ?? 0, 0, '.', ' '
                ) . " so'm</strong>",
                'footerOptions' => ['class' => 'text-end'],
                'value'         => fn($model) => '<span class="fw-medium text-success">'
                    . number_format($model->amount, 0, '.', ' ') . " so'm</span>",
            ],

            [
                'attribute' => 'reason',
                'label'     => 'Sabab',
            ],

            [
                'attribute' => 'created_at',
                'label'     => 'Sana',
                'filter'    => Html::activeInput('date', $searchModel, 'created_at_from',
                                ['class' => 'form-control form-control-sm mb-1', 'placeholder' => 'Dan'])
                             . Html::activeInput('date', $searchModel, 'created_at_to',
                                ['class' => 'form-control form-control-sm', 'placeholder' => 'Gacha']),
            ],

            [
                'class'      => ActionColumn::class,
                'template'   => '{view} {delete}',
                'urlCreator' => fn($action, $model) => Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
