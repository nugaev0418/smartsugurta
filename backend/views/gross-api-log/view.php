<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\GrossApiLog $model */

$this->title = 'Gross API log #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Gross API loglari', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$pretty = function (?string $json): string {
    if ($json === null || $json === '') {
        return '—';
    }
    $decoded = json_decode($json, true);
    $text = $decoded !== null
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $json;

    return '<pre class="mb-0" style="white-space: pre-wrap;">' . Html::encode($text) . '</pre>';
};
?>
<div class="gross-api-log-view">

    <div class="mb-3">
        <?= Html::a('<i class="ti ti-arrow-left me-1"></i>Ro\'yxatga qaytish', ['index'], ['class' => 'btn btn-secondary']) ?>
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
            'run_id',
            'label',
            'attempt',
            [
                'attribute' => 'success',
                'format'    => 'raw',
                'value'     => $model->success
                    ? '<span class="badge bg-success">OK</span>'
                    : '<span class="badge bg-danger">Xato</span>',
            ],
            'error_message:ntext',
            'police_id',
            'created_at',
            [
                'attribute' => 'request',
                'format'    => 'raw',
                'value'     => $pretty($model->request),
            ],
            [
                'attribute' => 'response',
                'format'    => 'raw',
                'value'     => $pretty($model->response),
            ],
        ],
    ]) ?>

</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
