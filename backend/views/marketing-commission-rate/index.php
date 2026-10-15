<?php

use common\models\MarketingCommissionRate;
use common\models\Police;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;

/** @var yii\web\View $this */
/** @var ActiveDataProvider $dataProvider */

$this->title = "Komissiya stavkalari";
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="marketing-commission-rate-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>Yangi stavka', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'provider_id',
                'label'     => 'Provayder',
                'value'     => fn($model) => Police::getProviderList()[$model->provider_id] ?? "Provayder #{$model->provider_id}",
            ],

            [
                'attribute' => 'region',
                'label'     => 'Hudud',
                'value'     => fn($model) => MarketingCommissionRate::getRegionList()[$model->region] ?? $model->region,
            ],

            [
                'attribute' => 'percent',
                'label'     => 'Komissiya, %',
                'value'     => fn($model) => $model->percent . '%',
            ],

            'updated_at',

            [
                'label'  => '',
                'format' => 'raw',
                'value'  => function ($model) {
                    return Html::a('<i class="ti ti-edit"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary me-1'])
                        . Html::a(
                            '<i class="ti ti-trash"></i>',
                            ['delete', 'id' => $model->id],
                            [
                                'class' => 'btn btn-sm btn-danger',
                                'data'  => [
                                    'confirm' => "Stavka o'chirilsinmi?",
                                    'method'  => 'post',
                                ],
                            ]
                        );
                },
            ],
        ],
    ]); ?>

</div>
