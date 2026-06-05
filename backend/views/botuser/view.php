<?php

use common\models\Botuser;
use common\models\Police;
use common\models\Payment;
use common\models\Income;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Botuser $model */

$this->title = 'Foydalanuvchi #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Foydalanuvchilar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$policeCount     = Police::find()->where(['user_id' => $model->id])->count();
$paidPoliceCount = Police::find()->where(['user_id' => $model->id, 'payment_status' => 1])->count();
$paymentCount    = Payment::find()->where(['user_id' => $model->id])->count();
$totalWithdrawn  = (int)(Payment::find()->where(['user_id' => $model->id, 'status' => Payment::STATUS_SUCCESS])->sum('amount') ?? 0);
$totalIncome     = (int)(Income::find()->where(['user_id' => $model->id])->sum('amount') ?? 0);
$referralCount   = Botuser::find()->where(['referred_by' => $model->id])->count();
?>
<div class="botuser-view">

    <div class="d-flex gap-2 mb-3">
        <?= Html::a('<i class="ti ti-edit me-1"></i>Tahrirlash', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="ti ti-trash me-1"></i>O\'chirish', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data'  => [
                'confirm' => 'Rostdan ham o\'chirmoqchimisiz?',
                'method'  => 'post',
            ],
        ]) ?>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small mb-1">Balans</div>
                    <div class="fw-bold text-success fs-4">
                        <?= number_format($model->balance ?? 0, 0, '.', ' ') ?> so'm
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small mb-1">Sug'urtalar</div>
                    <div class="fw-bold fs-4">
                        <?= $paidPoliceCount ?> / <?= $policeCount ?>
                        <span class="text-muted small fw-normal">to'langan</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small mb-1">Chiqarishlar</div>
                    <div class="fw-bold fs-4">
                        <?= $paymentCount ?> ta
                    </div>
                    <div class="text-danger small"><?= number_format($totalWithdrawn, 0, '.', ' ') ?> so'm</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small mb-1">Referal daromad</div>
                    <div class="fw-bold text-primary fs-4">
                        <?= number_format($totalIncome, 0, '.', ' ') ?> so'm
                    </div>
                    <div class="text-muted small"><?= $referralCount ?> referal</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main info -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-user me-2"></i>Asosiy ma'lumotlar</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <tr>
                                <td class="text-muted w-40">ID</td>
                                <td><?= $model->id ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Chat ID</td>
                                <td><code><?= Html::encode($model->chat_id) ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ism</td>
                                <td><?= Html::encode(trim("{$model->fname} {$model->lname}") ?: '—') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Username</td>
                                <td><?= $model->username ? '@' . Html::encode($model->username) : '—' ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Telefon</td>
                                <td><?= Html::encode($model->phone ?: '—') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Holat</td>
                                <td>
                                    <?= $model->status == 1
                                        ? '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Aktiv</span>'
                                        : '<span class="badge bg-danger-lt text-danger"><i class="ti ti-ban me-1"></i>Bloklangan</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Admin</td>
                                <td>
                                    <?= $model->is_admin
                                        ? '<span class="badge bg-purple-lt text-purple"><i class="ti ti-shield me-1"></i>Ha</span>'
                                        : '<span class="text-muted">Yo\'q</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Qo'shilgan</td>
                                <td><?= Html::encode($model->created_at ?: '—') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-users me-2"></i>Referal ma'lumotlari</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <tr>
                                <td class="text-muted w-40">Referal kodi</td>
                                <td>
                                    <?= $model->referral_code
                                        ? '<code>' . Html::encode($model->referral_code) . '</code>'
                                        : '<span class="text-muted">—</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Referal havola</td>
                                <td>
                                    <?php if ($model->referral_code): ?>
                                        <code class="small">t.me/smartsugurtabot?start=ref_<?= Html::encode($model->referral_code) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Taklif qilgan</td>
                                <td>
                                    <?php
                                    if ($model->referred_by) {
                                        $referrer = Botuser::findOne($model->referred_by);
                                        if ($referrer) {
                                            $name = trim("{$referrer->fname} {$referrer->lname}") ?: "ID:{$referrer->id}";
                                            echo Html::a(
                                                '<i class="ti ti-user me-1"></i>' . Html::encode($name),
                                                ['view', 'id' => $referrer->id],
                                                ['class' => 'text-decoration-none']
                                            );
                                        } else {
                                            echo $model->referred_by;
                                        }
                                    } else {
                                        echo '<span class="text-muted">—</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Referallar soni</td>
                                <td>
                                    <span class="fw-medium"><?= $referralCount ?></span> ta
                                    <?php if ($referralCount > 0): ?>
                                        <?= Html::a(
                                            '<i class="ti ti-arrow-right ms-1"></i>Ko\'rish',
                                            Url::to(['/botuser/index', 'BotuserSearch[referred_by]' => $model->id]),
                                            ['class' => 'btn btn-sm btn-outline-secondary ms-2']
                                        ) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Referal daromad</td>
                                <td class="fw-medium text-primary">
                                    <?= number_format($totalIncome, 0, '.', ' ') ?> so'm
                                    <?php if ($totalIncome > 0): ?>
                                        <?= Html::a(
                                            '<i class="ti ti-arrow-right ms-1"></i>Ko\'rish',
                                            Url::to(['/income/index', 'IncomeSearch[user_id]' => $model->id]),
                                            ['class' => 'btn btn-sm btn-outline-secondary ms-2']
                                        ) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
