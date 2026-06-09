<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Deeplink $model */

$this->title = 'Yangi Deeplink';
$this->params['breadcrumbs'][] = ['label' => 'Deeplink statistikasi', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-link me-2"></i>Yangi deeplink yaratish</h3>
            </div>

            <?php $form = ActiveForm::begin(['action' => ['create'], 'method' => 'post']); ?>

            <div class="card-body">
                <?= $form->field($model, 'name')->textInput([
                    'class'       => 'form-control',
                    'placeholder' => 'Masalan: Blogger Jasur, @uzauto kanali',
                    'autofocus'   => true,
                ])->label('Nom (blogger / kanal)') ?>

                <div class="text-muted small mt-1">
                    <i class="ti ti-info-circle me-1"></i>
                    Kod avtomatik generatsiya qilinadi. Havola ko'rinishi:
                    <code>t.me/smartsugurtabot?start=dlXXXXXXXX</code>
                </div>
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
