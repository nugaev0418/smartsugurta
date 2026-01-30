<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Police $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="police-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'policeId')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'startAt')->textInput() ?>

    <?= $form->field($model, 'endAt')->textInput() ?>

    <?= $form->field($model, 'pdfUrl')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->textInput() ?>

    <?= $form->field($model, 'paymentId')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'paymentLink')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'gateway')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'amount')->textInput() ?>

    <?= $form->field($model, 'driverRestriction')->textInput() ?>

    <?= $form->field($model, 'season_id')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
