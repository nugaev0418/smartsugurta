<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;

/** @var yii\web\View $this */
/** @var ActiveDataProvider $dataProvider */

$this->title = 'Deeplink statistikasi';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="deeplink-index">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= Html::a('<i class="ti ti-plus me-1"></i>Yangi deeplink', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',

            [
                'attribute' => 'name',
                'label'     => 'Nom (blogger/kanal)',
            ],

            [
                'attribute' => 'code',
                'label'     => 'Kod',
                'format'    => 'raw',
                'value'     => fn($model) => '<code>' . Html::encode($model->code) . '</code>',
            ],

            [
                'label'  => 'Havola',
                'format' => 'raw',
                'value'  => function ($model) {
                    $link = Html::encode($model->getLink());
                    return '<div class="d-flex align-items-center gap-2">'
                         . "<code class=\"small\">{$link}</code>"
                         . '<button class="btn btn-sm btn-outline-secondary copy-btn" data-link="' . $link . '">'
                         . '<i class="ti ti-copy"></i>'
                         . '</button></div>';
                },
            ],

            [
                'label'  => 'O\'tishlar',
                'format' => 'raw',
                'value'  => function ($model) {
                    $count = $model->getClickCount();
                    $url   = Url::to(['/botuser/index', 'BotuserSearch[deeplink_code]' => $model->code]);
                    return '<span class="fw-bold fs-5">' . $count . '</span> ta '
                         . ($count > 0 ? Html::a('<i class="ti ti-arrow-right ms-1"></i>', $url, ['class' => 'btn btn-sm btn-outline-primary']) : '');
                },
            ],

            'created_at',

            [
                'label'  => '',
                'format' => 'raw',
                'value'  => fn($model) => Html::a(
                    '<i class="ti ti-trash"></i>',
                    ['delete', 'id' => $model->id],
                    [
                        'class' => 'btn btn-sm btn-danger',
                        'data'  => [
                            'confirm' => "'{$model->name}' deeplink o'chirilsinmi?",
                            'method'  => 'post',
                        ],
                    ]
                ),
            ],
        ],
    ]); ?>

</div>

<?php
$this->registerJs(<<<JS
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.copy-btn');
    if (!btn) return;
    navigator.clipboard.writeText(btn.dataset.link).then(function() {
        btn.innerHTML = '<i class="ti ti-check text-success"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="ti ti-copy"></i>'; }, 2000);
    });
});
JS);
?>
