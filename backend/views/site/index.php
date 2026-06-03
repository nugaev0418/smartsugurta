<?php

/** @var yii\web\View $this */
/** @var array $stats */

$this->title = 'Dashboard';

$fmt = fn(int $n) => number_format($n, 0, '.', ' ');

$periods = [
    'day'   => ['label' => 'Bugun',    'sub' => date('d.m.Y'),                                          'icon' => 'ti-calendar-day',   'color' => 'blue'],
    'week'  => ['label' => 'Bu hafta', 'sub' => date('d.m') . ' – ' . date('d.m.Y'),                   'icon' => 'ti-calendar-week',  'color' => 'indigo'],
    'month' => ['label' => 'Bu oy',    'sub' => date('F Y'),                                            'icon' => 'ti-calendar-month', 'color' => 'purple'],
];
?>

<div class="row row-deck row-cards">

    <?php foreach ($periods as $key => $period): $s = $stats[$key]; ?>
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

                    <!-- Sug'urtalar soni -->
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

                    <!-- To'langan -->
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-circle-check me-1 text-success"></i>
                            To'langan
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-success"><?= $fmt($s['paid_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>

                    <!-- To'lanmagan -->
                    <tr class="border-top">
                        <td class="text-muted">
                            <i class="ti ti-circle-x me-1 text-danger"></i>
                            To'lanmagan
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-danger"><?= $fmt($s['unpaid_amount']) ?></span>
                            <span class="text-muted small ms-1">so'm</span>
                        </td>
                    </tr>

                </table>
            </div>

            <!-- Progress bar: to'langan ulushi -->
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
                    <div class="progress-bar bg-success"
                         style="width: <?= $pct ?>%"
                         role="progressbar"
                         aria-valuenow="<?= $pct ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

</div>
