<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\PaynetSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Paynet tokenlar';
$this->params['breadcrumbs'][] = $this->title;

$activeList = [0 => 'Nofaol', 1 => 'Faol'];
?>
<div class="paynet-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>' . Yii::t('app', 'Create Paynet'), ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'name',
            'paynet_id',

            [
                'attribute' => 'api_token',
                'label'     => 'Api Token',
                'format'    => 'raw',
                'value'     => fn($model) => $model->api_token
                    ? '<code>' . Html::encode(substr($model->api_token, 0, 6)) . '…</code>'
                    : '—',
                'filter'    => false,
            ],

            [
                'attribute' => 'is_active',
                'label'     => 'Holat',
                'format'    => 'raw',
                'filter'    => $activeList,
                'value'     => fn($model) => $model->is_active
                    ? '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Faol</span>'
                    : '<span class="badge bg-secondary-lt text-secondary"><i class="ti ti-circle-x me-1"></i>Nofaol</span>',
            ],

            'created_at',

            [
                'class'      => ActionColumn::class,
                'template'   => '{activate} {deactivate} {view} {update} {delete}',
                'urlCreator' => fn($action, $model) => Url::toRoute([$action, 'id' => $model->id]),
                'buttons' => [
                    'activate' => fn($url, $model) => $model->is_active ? '' : Html::a('<i class="ti ti-toggle-right"></i>', $url, [
                        'title' => 'Faollashtirish',
                        'class' => 'text-success',
                        'data'  => ['method' => 'post', 'confirm' => 'Ushbu tokenni faollashtirmoqchimisiz?'],
                    ]),
                    'deactivate' => fn($url, $model) => $model->is_active ? Html::a('<i class="ti ti-toggle-left"></i>', $url, [
                        'title' => 'Faolsizlantirish',
                        'class' => 'text-secondary',
                        'data'  => ['method' => 'post', 'confirm' => 'Ushbu tokenni faolsizlantirmoqchimisiz?'],
                    ]) : '',
                ],
            ],
        ],
    ]); ?>

</div>
