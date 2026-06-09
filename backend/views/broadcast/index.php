<?php

use common\models\Broadcast;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;

/** @var yii\web\View $this */
/** @var ActiveDataProvider $dataProvider */

$this->title = 'Broadcast';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<JS
    (function poll() {
        var hasSending = document.querySelectorAll('.badge-sending').length > 0;
        if (hasSending) {
            setTimeout(function() { location.reload(); }, 5000);
        }
    })();
JS);
?>
<div class="broadcast-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout'       => "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}",
        'tableOptions' => ['class' => 'table table-vcenter card-table'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',

            [
                'attribute' => 'message_type',
                'label'     => 'Tur',
                'format'    => 'raw',
                'value'     => function ($model) {
                    $icons = [
                        'text'       => 'ti-text-size',
                        'photo'      => 'ti-photo',
                        'video'      => 'ti-video',
                        'document'   => 'ti-file',
                        'audio'      => 'ti-music',
                        'voice'      => 'ti-microphone',
                        'sticker'    => 'ti-mood-happy',
                        'animation'  => 'ti-gif',
                        'video_note' => 'ti-video-plus',
                    ];
                    $icon = $icons[$model->message_type] ?? 'ti-message';
                    return '<i class="ti ' . $icon . ' me-1"></i>' . Html::encode($model->message_type);
                },
            ],

            [
                'attribute' => 'status',
                'label'     => 'Holat',
                'format'    => 'raw',
                'value'     => function ($model) {
                    return match ((int)$model->status) {
                        Broadcast::STATUS_SENDING => '<span class="badge bg-warning-lt text-warning badge-sending"><i class="ti ti-loader me-1"></i>Yuborilmoqda</span>',
                        Broadcast::STATUS_DONE    => '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Tugadi</span>',
                        default                   => '<span class="badge bg-secondary-lt text-secondary"><i class="ti ti-clock me-1"></i>Kutmoqda</span>',
                    };
                },
            ],

            [
                'label'  => 'Progress',
                'format' => 'raw',
                'value'  => function ($model) {
                    $pct  = $model->getProgressPercent();
                    $color = $pct >= 100 ? 'bg-success' : 'bg-primary';
                    return '<div class="d-flex align-items-center gap-2">'
                         . '<div class="progress flex-grow-1" style="height:8px">'
                         . "<div class=\"progress-bar {$color}\" style=\"width:{$pct}%\"></div>"
                         . '</div>'
                         . "<span class=\"small text-muted\">{$model->sent_count}/{$model->total_users} ({$pct}%)</span>"
                         . '</div>';
                },
            ],

            'created_at',

            [
                'label'  => '',
                'format' => 'raw',
                'value'  => function ($model) {
                    return Html::a(
                        '<i class="ti ti-trash me-1"></i>O\'chirish',
                        ['delete', 'id' => $model->id],
                        [
                            'class' => 'btn btn-sm btn-danger',
                            'data'  => [
                                'confirm' => "Broadcast #{$model->id} barcha foydalanuvchilardan o'chirilsinmi?",
                                'method'  => 'post',
                            ],
                        ]
                    );
                },
            ],
        ],
    ]); ?>

</div>
