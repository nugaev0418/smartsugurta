<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login';
?>
<h2 class="card-title text-center mb-4"><?= Html::encode($this->title) ?></h2>

<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

    <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => 'Username']) ?>

    <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Password']) ?>

    <?= $form->field($model, 'rememberMe')->checkbox() ?>

    <div class="form-footer">
        <?= Html::submitButton('Sign in', ['class' => 'btn btn-primary w-100', 'name' => 'login-button']) ?>
    </div>

<?php ActiveForm::end(); ?>
