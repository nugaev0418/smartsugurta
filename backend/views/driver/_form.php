<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Driver $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="driver-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'passportBirthdate')->textInput() ?>

    <?= $form->field($model, 'passportNumber')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'passportSeria')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'relativeId')->textInput() ?>

    <?= $form->field($model, 'police_id')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
