<?php

use common\models\Deeplink;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Deeplink $model */
/** @var yii\widgets\ActiveForm $form */

?>
<?= $form->field($model, 'name')->textInput([
    'class'       => 'form-control',
    'placeholder' => 'Masalan: Blogger Jasur, @uzauto kanali',
    'autofocus'   => $model->isNewRecord,
])->label('Nom (blogger / kanal)') ?>

<?php if (!$model->isNewRecord): ?>
    <div class="mb-3">
        <label class="form-label">Kod</label>
        <input type="text" class="form-control" value="<?= Html::encode($model->code) ?>" disabled>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-6">
        <?= $form->field($model, 'channel')->dropDownList(
            ['' => '—'] + Deeplink::getChannelList()
        ) ?>
    </div>
    <div class="col-6">
        <?= $form->field($model, 'source')->dropDownList(
            ['' => '—'] + Deeplink::getSourceList()
        ) ?>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <?= $form->field($model, 'cost')->textInput(['type' => 'number', 'step' => '0.01', 'min' => 0]) ?>
    </div>
    <div class="col-6">
        <?= $form->field($model, 'currency')->textInput(['maxlength' => 3]) ?>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <?= $form->field($model, 'starts_at')->input('date') ?>
    </div>
    <div class="col-6">
        <?= $form->field($model, 'ends_at')->input('date') ?>
    </div>
</div>

<?= $form->field($model, 'status')->dropDownList(Deeplink::getStatusList()) ?>

<?php if ($model->isNewRecord): ?>
    <div class="text-muted small mt-1">
        <i class="ti ti-info-circle me-1"></i>
        Kod avtomatik generatsiya qilinadi. Havola ko'rinishi:
        <code>t.me/smartsugurtabot?start=dlXXXXXXXX</code>
    </div>
<?php endif; ?>
