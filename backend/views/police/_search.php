<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\PoliceSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="police-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'policeId') ?>

    <?= $form->field($model, 'startAt') ?>

    <?= $form->field($model, 'endAt') ?>

    <?php // echo $form->field($model, 'pdfUrl') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'paymentId') ?>

    <?php // echo $form->field($model, 'paymentLink') ?>

    <?php // echo $form->field($model, 'gateway') ?>

    <?php // echo $form->field($model, 'amount') ?>

    <?php // echo $form->field($model, 'driverRestriction') ?>

    <?php // echo $form->field($model, 'season_id') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
