<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Income $model */

$this->title = 'Daromad #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Daromadlar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
                <?= Html::a('<i class="ti ti-arrow-left me-1"></i>Orqaga', ['index'], ['class' => 'btn btn-secondary btn-sm']) ?>
            </div>
            <div class="card-body">
                <?= DetailView::widget([
                    'model'      => $model,
                    'attributes' => [
                        'id',
                        [
                            'label' => 'Foydalanuvchi',
                            'value' => function ($model) {
                                $user = $model->user;
                                if (!$user) return '—';
                                return trim("{$user->fname} {$user->lname}") . " (ID: {$user->id})";
                            },
                        ],
                        [
                            'attribute' => 'amount',
                            'label'     => 'Miqdor',
                            'value'     => fn($model) => number_format($model->amount, 0, '.', ' ') . " so'm",
                        ],
                        'reason:text:Sabab',
                        'created_at:text:Sana',
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
