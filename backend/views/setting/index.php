<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Setting $model */

$this->title = 'Bot sozlamalari';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-adjustments me-2"></i>Bot sozlamalari</h3>
            </div>

            <?php $form = ActiveForm::begin([
                'action' => ['index'],
                'method' => 'post',
                'fieldConfig' => ['template' => "{label}\n{input}\n{error}"],
            ]); ?>

            <div class="card-body">

                <h4 class="mb-3 text-secondary">Holat sozlamalari</h4>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Bot holati</label>
                        <div>
                            <?= Html::activeCheckbox($model, 'bot_status', [
                                'class'     => 'form-check-input',
                                'value'     => 1,
                                'uncheck'   => 0,
                                'label'     => false,
                                'id'        => 'bot_status',
                            ]) ?>
                            <label class="form-check-label ms-2" for="bot_status">
                                <?= $model->bot_status ? '<span class="text-success">Yoqilgan</span>' : '<span class="text-danger">O\'chirilgan</span>' ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Polis holati</label>
                        <div>
                            <?= Html::activeCheckbox($model, 'police_status', [
                                'class'   => 'form-check-input',
                                'value'   => 1,
                                'uncheck' => 0,
                                'label'   => false,
                                'id'      => 'police_status',
                            ]) ?>
                            <label class="form-check-label ms-2" for="police_status">
                                <?= $model->police_status ? '<span class="text-success">Yoqilgan</span>' : '<span class="text-danger">O\'chirilgan</span>' ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To'lov holati</label>
                        <div>
                            <?= Html::activeCheckbox($model, 'payment_status', [
                                'class'   => 'form-check-input',
                                'value'   => 1,
                                'uncheck' => 0,
                                'label'   => false,
                                'id'      => 'payment_status',
                            ]) ?>
                            <label class="form-check-label ms-2" for="payment_status">
                                <?= $model->payment_status ? '<span class="text-success">Yoqilgan</span>' : '<span class="text-danger">O\'chirilgan</span>' ?>
                            </label>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3 text-secondary">Foiz sozlamalari</h4>

                <div class="row g-3">
                    <div class="col-md-4">
                        <?= $form->field($model, 'user_percent')->textInput([
                            'type'        => 'number',
                            'min'         => 0,
                            'max'         => 100,
                            'class'       => 'form-control',
                            'placeholder' => '0',
                        ])->label('Foydalanuvchi foizi (%)') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'tashkent_user_percent')->textInput([
                            'type'        => 'number',
                            'min'         => 0,
                            'max'         => 100,
                            'class'       => 'form-control',
                            'placeholder' => '0',
                        ])->label('Toshkent foizi (%)') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'referral_percent')->textInput([
                            'type'        => 'number',
                            'min'         => 0,
                            'max'         => 100,
                            'class'       => 'form-control',
                            'placeholder' => '0',
                        ])->label('Referal foizi (%)') ?>
                    </div>
                </div>

            </div>

            <div class="card-footer d-flex">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Saqlash
                </button>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
