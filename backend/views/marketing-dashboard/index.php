<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var string $from */
/** @var string $to */
/** @var string $touchType */
/** @var array $funnel */
/** @var array $conversion */

$this->title = 'Marketing dashboard';
$this->params['breadcrumbs'][] = $this->title;

$methodLabels = [
    'deeplink' => 'Deeplink',
    'referral' => 'Referral',
    'direct'   => "To'g'ridan-to'g'ri",
];
?>
<div class="marketing-dashboard-index">

    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <label class="form-label">Dan</label>
            <input type="date" name="from" value="<?= Html::encode($from) ?>" class="form-control">
        </div>
        <div class="col-auto">
            <label class="form-label">Gacha</label>
            <input type="date" name="to" value="<?= Html::encode($to) ?>" class="form-control">
        </div>
        <div class="col-auto">
            <label class="form-label">Attribution turi</label>
            <select name="touchType" class="form-select">
                <option value="first" <?= $touchType === 'first' ? 'selected' : '' ?>>Birinchi manba</option>
                <option value="last" <?= $touchType === 'last' ? 'selected' : '' ?>>Oxirgi manba</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filtrlash</button>
        </div>
        <div class="col-auto">
            <?= Html::a(
                '<i class="ti ti-download me-1"></i>CSV',
                Url::to(['export-csv', 'from' => $from, 'to' => $to, 'touchType' => $touchType]),
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </form>

    <div class="row mb-3">
        <div class="col-md-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small">Yangi foydalanuvchi</div>
                    <div class="fs-3 fw-bold"><?= $funnel['total_new_users'] ?></div>
                </div>
            </div>
        </div>
        <?php foreach ($methodLabels as $method => $label): ?>
            <div class="col-md-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-muted small"><?= Html::encode($label) ?></div>
                        <div class="fs-3 fw-bold"><?= $funnel['by_method'][$method] ?? 0 ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-md-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-muted small">So'rovnoma javob berdi</div>
                    <div class="fs-3 fw-bold"><?= $funnel['survey_answered'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kanal / kampaniya bo'yicha konversiya</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Manba</th>
                        <th>Kampaniya</th>
                        <th>Yangi foyd.</th>
                        <th>Xaridor</th>
                        <th>Xaridlar</th>
                        <th>Konversiya</th>
                        <th>Tushum</th>
                        <th>Xarajat</th>
                        <th>CAC</th>
                        <th>ROAS</th>
                        <th>Komissiya</th>
                        <th>Sof natija</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conversion as $row): ?>
                        <tr>
                            <td><?= Html::encode($row['source'] ?? '—') ?></td>
                            <td><?= $row['campaign'] ? '<code>' . Html::encode($row['campaign']) . '</code>' : '—' ?></td>
                            <td><?= $row['new_users'] ?></td>
                            <td><?= $row['buyers'] ?></td>
                            <td><?= $row['purchases'] ?></td>
                            <td><?= $row['conversion_rate'] !== null ? $row['conversion_rate'] . '%' : '—' ?></td>
                            <td><?= number_format($row['revenue'], 0, '.', ' ') ?></td>
                            <td><?= $row['cost'] ? number_format($row['cost'], 0, '.', ' ') . ' ' . $row['currency'] : '—' ?></td>
                            <td><?= $row['cac'] !== null ? number_format($row['cac'], 0, '.', ' ') : '—' ?></td>
                            <td><?= $row['roas'] !== null ? $row['roas'] : '—' ?></td>
                            <td><?= $row['commission'] !== null ? number_format($row['commission'], 0, '.', ' ') : '<span class="text-muted">noma\'lum</span>' ?></td>
                            <td><?= $row['net_result'] !== null ? number_format($row['net_result'], 0, '.', ' ') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$conversion): ?>
                        <tr><td colspan="12" class="text-center text-muted">Ma'lumot yo'q</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
