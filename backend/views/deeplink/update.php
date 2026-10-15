<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Deeplink $model */

$this->title = 'Deeplinkni tahrirlash: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Deeplink statistikasi', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Tahrirlash';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-edit me-2"></i>Deeplinkni tahrirlash</h3>
            </div>

            <?php $form = ActiveForm::begin(['action' => ['update', 'id' => $model->id], 'method' => 'post']); ?>

            <div class="card-body">
                <?= $this->render('_form', ['model' => $model, 'form' => $form]) ?>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="ti ti-check me-1"></i>Saqlash
                </button>
                <?= Html::a('Bekor qilish', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
