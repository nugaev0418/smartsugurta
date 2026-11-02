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
                    <div class="col-md-4">
                        <label class="form-label">Mening avtolarim holati</label>
                        <div>
                            <?= Html::activeCheckbox($model, 'my_vehicles_status', [
                                'class'   => 'form-check-input',
                                'value'   => 1,
                                'uncheck' => 0,
                                'label'   => false,
                                'id'      => 'my_vehicles_status',
                            ]) ?>
                            <label class="form-check-label ms-2" for="my_vehicles_status">
                                <?= $model->my_vehicles_status ? '<span class="text-success">Yoqilgan</span>' : '<span class="text-danger">O\'chirilgan</span>' ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Admin online</label>
                        <div>
                            <?= Html::activeCheckbox($model, 'admin_online', [
                                'class'   => 'form-check-input',
                                'value'   => 1,
                                'uncheck' => 0,
                                'label'   => false,
                                'id'      => 'admin_online',
                            ]) ?>
                            <label class="form-check-label ms-2" for="admin_online">
                                <?= $model->admin_online ? '<span class="text-success">Online</span>' : '<span class="text-danger">Offline</span>' ?>
                            </label>
                            <div class="form-text">Yoqilgan bo'lsa, polisa yaratish muvaffaqiyatsiz bo'lganda botda sizga "qayta urinish" tugmalari keladi (foydalanuvchi shu vaqt kutadi, 10 daqiqada javob bo'lmasa unga uzilish xabari yuboriladi). O'chiq bo'lsa foydalanuvchiga darhol xabar yuboriladi.</div>
                        </div>
                    </div>
                </div>

                <?php $pauseUntil = \common\models\Setting::getUzbekinvestPauseUntil(); ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Uzbekinvest pauza</label>
                        <?= Html::activeDropDownList($model, 'pause_minutes', [
                            15 => '15 daqiqaga yoqish',
                            30 => '30 daqiqaga yoqish',
                            60 => '1 soatga yoqish',
                            120 => '2 soatga yoqish',
                            'end' => 'Pauzani tugatish',
                        ], ['class' => 'form-select', 'prompt' => "O'zgartirmaslik"]) ?>
                        <div class="mt-2">
                            <?= $pauseUntil
                                ? '<span class="badge bg-warning-lt text-warning">Pauza faol: ' . Html::encode(date('H:i', strtotime($pauseUntil))) . ' gacha</span>'
                                : '<span class="badge bg-secondary-lt text-secondary">Pauza faol emas</span>' ?>
                        </div>
                        <div class="form-text">Brauzerdan Uzbekinvest'ga kirish uchun: pauzani yoqing, 1-2 daqiqa kuting (ishlayotgan ariza tugasin), ishlang, tugagach "Pauzani tugatish"ni tanlang. Pauzada bot Uzbekinvest'ga kirmaydi: yangi arizalar Gross'ga yo'naltiriladi, to'lov tekshiruvi to'xtaydi. Pauza belgilangan vaqtda o'zi tugaydi.</div>
                    </div>
                </div>

                <h4 class="mb-3 text-secondary">Provayder</h4>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <?= $form->field($model, 'osago_provider')->dropDownList([
                            \common\models\Police::PROVIDER_GROSS       => 'Gross Insurance',
                            \common\models\Police::PROVIDER_UZBEKINVEST => 'Uzbekinvest',
                        ], ['class' => 'form-select']) ?>
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
