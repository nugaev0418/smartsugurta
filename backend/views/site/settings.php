<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $errors */
/** @var bool $success */

$this->title = 'Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-lock me-2"></i>Parolni almashtirish</h3>
            </div>

            <?php if ($success): ?>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="ti ti-circle-check me-2"></i>Parol muvaffaqiyatli o'zgartirildi.
                </div>
                <?= Html::a('<i class="ti ti-arrow-left me-1"></i>Bosh sahifa', ['/site/index'], ['class' => 'btn btn-secondary']) ?>
            </div>
            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="card-body pb-0">
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="ti ti-alert-circle me-1"></i><?= Html::encode($error) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?= Html::beginForm(['settings'], 'post', ['class' => 'card-body']) ?>

                <div class="mb-3">
                    <label class="form-label required">Joriy parol</label>
                    <input type="password" name="current_password" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Yangi parol</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>

                <div class="mb-3">
                    <label class="form-label required">Yangi parolni tasdiqlang</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Saqlash
                    </button>
                    <?= Html::a('Bekor qilish', ['/site/index'], ['class' => 'btn btn-secondary']) ?>
                </div>

            <?= Html::endForm() ?>

            <?php endif; ?>
        </div>
    </div>
</div>
