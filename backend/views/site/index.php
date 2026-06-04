<?php

/** @var yii\web\View $this */
/** @var array $policeStats */
/** @var array $paymentStats */
/** @var array $topUsersMonth */
/** @var array $topUsersAllTime */

$this->title = 'Dashboard';

$fmt = fn(int $n) => number_format($n, 0, '.', ' ');

$periods = [
    'day'   => ['label' => 'Bugun',    'sub' => date('d.m.Y'),                        'icon' => 'ti-calendar-day',   'color' => 'blue'],
    'week'  => ['label' => '7 Kunda', 'sub' => date('d.m') . ' – ' . date('d.m.Y'), 'icon' => 'ti-calendar-week',  'color' => 'indigo'],
    'month' => ['label' => '30 kunda',    'sub' => date('F Y'),                          'icon' => 'ti-calendar-month', 'color' => 'purple'],
];
?>

<!-- ========== SUG'URTALAR ========== -->
<div class="row mb-2">
    <div class="col">
        <h3 class="mb-0">
            <i class="ti ti-shield-check me-2 text-blue"></i>Sug'urtalar
        </h3>
    </div>
</div>

<div class="row row-deck row-cards mb-4">
    <?php foreach ($periods as $key => $period): $s = $policeStats[$key]; ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card">

            <div class="card-header">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-<?= $period['color'] ?>-lt text-<?= $period['color'] ?>">
                        <i class="ti <?= $period['icon'] ?>"></i>
                    </span>
                    <div>
                        <div class="fw-bold"><?= $period['label'] ?></div>
                        <div class="text-muted small"><?= $period['sub'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted">
                            <i class="ti ti-file-invoice me-1 text-<?= $period['color'] ?>"></i>
                            Sug'urtalar soni
                        </td>
                        <td class="text-end">
                            <span class="fw-bold fs-4"><?= $s['count'] ?></span>
                            <span class="text-muted small ms-1">ta</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-circle-check me-1 text-success"></i>
                            To'langan sug'urtalar
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-success fs-4"><?= $s['paid_count'] ?></span>
                            <span class="text-muted small ms-1">ta</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted ps-4">
                            <i class="ti ti-cash me-1 text-success"></i>
                            To'langan summa
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-success"><?= $fmt($s['paid_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-circle-x me-1 text-danger"></i>
                            To'lanmagan summa
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-danger"><?= $fmt($s['unpaid_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>
                </table>
            </div>

            <?php
                $total = $s['paid_amount'] + $s['unpaid_amount'];
                $pct   = $total > 0 ? round($s['paid_amount'] / $total * 100) : 0;
            ?>
            <div class="card-footer p-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>To'lov ulushi</span>
                    <span><?= $pct ?>%</span>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%"
                         role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ========== TO'LOVLAR ========== -->
<div class="row mb-2">
    <div class="col">
        <h3 class="mb-0">
            <i class="ti ti-credit-card me-2 text-green"></i>To'lovlar (Pul chiqarish)
        </h3>
    </div>
</div>

<div class="row row-deck row-cards">
    <?php foreach ($periods as $key => $period): $p = $paymentStats[$key]; ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card">

            <div class="card-header">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-<?= $period['color'] ?>-lt text-<?= $period['color'] ?>">
                        <i class="ti <?= $period['icon'] ?>"></i>
                    </span>
                    <div>
                        <div class="fw-bold"><?= $period['label'] ?></div>
                        <div class="text-muted small"><?= $period['sub'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted">
                            <i class="ti ti-list me-1 text-<?= $period['color'] ?>"></i>
                            Jami to'lovlar
                        </td>
                        <td class="text-end">
                            <span class="fw-bold fs-4"><?= $p['count'] ?></span>
                            <span class="text-muted small ms-1">ta</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-circle-check me-1 text-success"></i>
                            Muvaffaqiyatli
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-success fs-4"><?= $p['success_count'] ?></span>
                            <span class="text-muted small ms-1">ta</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted ps-4">
                            <i class="ti ti-cash me-1 text-success"></i>
                            Muvaffaqiyatli summa
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-success"><?= $fmt($p['success_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-clock me-1 text-warning"></i>
                            Jarayondagi summa
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-warning"><?= $fmt($p['process_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-ban me-1 text-danger"></i>
                            Bekor qilingan
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-danger"><?= $p['cancel_count'] ?></span>
                            <span class="text-muted small ms-1">ta</span>
                        </td>
                    </tr>
                </table>
            </div>

            <?php
                $pct = $p['count'] > 0 ? round($p['success_count'] / $p['count'] * 100) : 0;
            ?>
            <div class="card-footer p-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Muvaffaqiyat ulushi</span>
                    <span><?= $pct ?>%</span>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%"
                         role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ========== TOP 10 USERS ========== -->
<div class="row mt-4 mb-2">
    <div class="col">
        <h3 class="mb-0">
            <i class="ti ti-trophy me-2 text-yellow"></i>Eng faol mijozlar (to'langan sug'urtalar bo'yicha)
        </h3>
    </div>
</div>

<?php
$renderTopTable = function(array $users, string $title, string $color) use ($fmt): void { ?>
<div class="col-lg-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title d-flex align-items-center gap-2">
                <span class="avatar avatar-sm bg-<?= $color ?>-lt text-<?= $color ?>">
                    <i class="ti ti-chart-bar"></i>
                </span>
                <span class="fw-bold"><?= $title ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($users)): ?>
                <div class="text-center text-muted py-4">Ma'lumot yo'q</div>
            <?php else: ?>
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th class="w-1">#</th>
                        <th>Mijoz</th>
                        <th class="text-center">Sug'urtalar</th>
                        <th class="text-end">Jami summa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td>
                            <?php if ($i === 0): ?>
                                <span class="badge bg-yellow text-yellow-fg">1</span>
                            <?php elseif ($i === 1): ?>
                                <span class="badge bg-secondary">2</span>
                            <?php elseif ($i === 2): ?>
                                <span class="badge bg-orange-lt text-orange">3</span>
                            <?php else: ?>
                                <span class="text-muted"><?= $i + 1 ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-xs bg-blue-lt text-blue">
                                    <i class="ti ti-user"></i>
                                </span>
                                <div>
                                    <?php $name = trim($u['fname'] . ' ' . $u['lname']); ?>
                                    <a href="#" class="user-info-link fw-medium text-decoration-none"
                                       data-user-id="<?= $u['user_id'] ?>">
                                        <?= htmlspecialchars($name) ?: 'ID #' . $u['user_id'] ?>
                                    </a>
                                    <?php if ($u['username']): ?>
                                    <div class="text-muted small">@<?= htmlspecialchars($u['username']) ?></div>
                                    <?php elseif ($u['phone']): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($u['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-blue-lt text-blue"><?= $u['count'] ?> ta</span>
                        </td>
                        <td class="text-end fw-bold text-success">
                            <?= $fmt((int)$u['total']) ?> <span class="text-muted small fw-normal">so'm</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php }; ?>

<div class="row row-deck row-cards">
    <?php $renderTopTable($topUsersMonth,   "So'nggi 1 oyda", 'indigo'); ?>
    <?php $renderTopTable($topUsersAllTime, 'Bot ishga tushganidan beri', 'purple'); ?>
</div>

<?= $this->renderFile('@backend/views/shared/_user_modal.php') ?>
