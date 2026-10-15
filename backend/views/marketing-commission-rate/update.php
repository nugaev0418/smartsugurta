<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\MarketingCommissionRate $model */

$this->title = 'Komissiya stavkasini tahrirlash';
$this->params['breadcrumbs'][] = ['label' => 'Komissiya stavkalari', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Tahrirlash';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-edit me-2"></i>Komissiya stavkasini tahrirlash</h3>
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
