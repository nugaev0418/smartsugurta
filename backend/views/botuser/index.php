<?php

use common\models\Botuser;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\BotuserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Foydalanuvchilar';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="botuser-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>Yangi foydalanuvchi', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',

            [
                'attribute' => 'fname',
                'label'     => 'Ism',
                'format'    => 'raw',
                'value'     => fn($model) => Html::a(
                    '<i class="ti ti-user me-1"></i>' . Html::encode(trim("{$model->fname} {$model->lname}") ?: '—'),
                    '#',
                    ['class' => 'user-info-link text-decoration-none', 'data-user-id' => $model->id]
                ),
            ],

            [
                'attribute' => 'chat_id',
                'label'     => 'Chat ID',
            ],

            [
                'attribute' => 'balance',
                'label'     => 'Balans',
                'format'    => 'raw',
                'value'     => fn($model) => '<span class="fw-medium text-success">'
                    . number_format($model->balance ?? 0, 0, '.', ' ') . " so'm</span>",
            ],

            [
                'attribute' => 'status',
                'label'     => 'Holat',
                'format'    => 'raw',
                'filter'    => [1 => 'Aktiv', 0 => 'Bloklangan'],
                'value'     => fn($model) => $model->status == 1
                    ? '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Aktiv</span>'
                    : '<span class="badge bg-danger-lt text-danger"><i class="ti ti-ban me-1"></i>Bloklangan</span>',
            ],

            [
                'attribute' => 'referral_code',
                'label'     => 'Referal kodi',
                'format'    => 'raw',
                'value'     => fn($model) => $model->referral_code
                    ? '<code>' . Html::encode($model->referral_code) . '</code>'
                    : '<span class="text-muted">—</span>',
            ],

            [
                'attribute' => 'referred_by',
                'label'     => 'Taklif qilgan',
                'format'    => 'raw',
                'value'     => function ($model) {
                    if (!$model->referred_by) return '<span class="text-muted">—</span>';
                    $referrer = Botuser::findOne($model->referred_by);
                    if (!$referrer) return $model->referred_by;
                    $name = trim("{$referrer->fname} {$referrer->lname}") ?: "ID:{$referrer->id}";
                    return Html::a(
                        '<i class="ti ti-user me-1"></i>' . Html::encode($name),
                        '#',
                        ['class' => 'user-info-link text-decoration-none', 'data-user-id' => $referrer->id]
                    );
                },
            ],

            'created_at',

            [
                'class'      => ActionColumn::class,
                'urlCreator' => fn($action, Botuser $model) => Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
