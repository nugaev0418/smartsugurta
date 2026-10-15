<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\MarketingCommissionRate $model */

$this->title = 'Yangi komissiya stavkasi';
$this->params['breadcrumbs'][] = ['label' => 'Komissiya stavkalari', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-percentage me-2"></i>Yangi komissiya stavkasi</h3>
            </div>

            <?php $form = ActiveForm::begin(['action' => ['create'], 'method' => 'post']); ?>

            <div class="card-body">
                <?= $this->render('_form', ['model' => $model, 'form' => $form]) ?>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="ti ti-plus me-1"></i>Yaratish
                </button>
                <?= Html::a('Bekor qilish', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
