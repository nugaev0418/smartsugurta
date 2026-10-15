<?php

namespace backend\component;

use common\models\Deeplink;
use common\models\MarketingAttribution;
use common\models\MarketingTouch;
use common\models\Police;
use yii\db\Query;

/**
 * Marketing dashboard uchun jonli SQL agregatsiya — hech qanday son
 * keshlanmaydi/oldindan hisoblanmaydi, har bir chaqiruv joriy ma'lumotlar
 * bazasidan qayta hisoblaydi (shu sabab Police statusi keyinroq o'zgarsa
 * ham, keyingi so'rov avtomatik to'g'ri ko'rsatadi).
 */
class MarketingReportService
{
    /**
     * Berilgan davrda birinchi marta ko'ringan (first_seen_at) foydalanuvchilar
     * soni, aniqlash usuli (source_method) bo'yicha bo'linib.
     */
    public function getFunnel(string $from, string $to): array
    {
        $rows = (new Query())
            ->select(['source_method', 'cnt' => 'COUNT(*)'])
            ->from(MarketingAttribution::tableName())
            ->where(['between', 'first_seen_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('source_method')
            ->all();

        $byMethod = [
            MarketingAttribution::METHOD_DEEPLINK => 0,
            MarketingAttribution::METHOD_REFERRAL => 0,
            MarketingAttribution::METHOD_DIRECT => 0,
        ];
        $total = 0;
        foreach ($rows as $row) {
            $method = $row['source_method'] ?: MarketingAttribution::METHOD_DIRECT;
            $byMethod[$method] = (int)$row['cnt'];
            $total += (int)$row['cnt'];
        }

        $surveyAnswered = (new Query())
            ->from(MarketingAttribution::tableName())
            ->where(['between', 'first_seen_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->andWhere(['not', ['survey_source' => null]])
            ->count();

        return [
            'total_new_users' => $total,
            'by_method' => $byMethod,
            'survey_answered' => (int)$surveyAnswered,
            'unresolved' => $byMethod[MarketingAttribution::METHOD_DIRECT] - (int)$surveyAnswered,
        ];
    }

    /**
     * Kanal/manba/kampaniya bo'yicha konversiya+pul hisoboti.
     * $touchType 'first' yoki 'last' — qaysi attribution ustuni bo'yicha
     * guruhlanishini belgilaydi (ikkalasi aralashtirilmaydi — alohida
     * chaqiriladi). Acquisition cohort (yangi foydalanuvchilar,
     * first_seen_at oralig'ida) va purchase-date cohort (to'langan polislar,
     * police.created_at oralig'ida) atayin ARALASHTIRILMAYDI — ikki alohida
     * so'rov, bitta kalit (source|campaign) bo'yicha birlashtiriladi.
     */
    public function getConversion(string $from, string $to, string $touchType = 'first'): array
    {
        $sourceCol = $touchType === 'last' ? 'last_source' : 'first_source';
        $campaignCol = $touchType === 'last' ? 'last_campaign' : 'first_campaign';

        $newUsersRows = (new Query())
            ->select(['source' => $sourceCol, 'campaign' => $campaignCol, 'new_users' => 'COUNT(*)'])
            ->from(MarketingAttribution::tableName())
            ->where(['between', 'first_seen_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy([$sourceCol, $campaignCol])
            ->all();

        $purchaseRows = $this->fetchPurchaseRows($from, $to);

        $groups = [];
        foreach ($newUsersRows as $row) {
            $key = $this->groupKey($row['source'], $row['campaign']);
            $groups[$key] = $this->emptyGroup($row['source'], $row['campaign']);
            $groups[$key]['new_users'] = (int)$row['new_users'];
        }

        foreach ($purchaseRows as $row) {
            $source = $touchType === 'last' ? $row['last_source'] : $row['first_source'];
            $campaign = $touchType === 'last' ? $row['last_campaign'] : $row['first_campaign'];
            $key = $this->groupKey($source, $campaign);

            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyGroup($source, $campaign);
            }

            $groups[$key]['buyer_ids'][$row['user_id']] = true;
            $groups[$key]['purchases']++;
            $groups[$key]['revenue'] += (float)$row['amount'];

            $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : null;
            $percent = $metadata['commission_percent'] ?? null;
            if ($percent !== null) {
                $groups[$key]['commission_known'] += (float)$row['amount'] * (float)$percent / 100;
            } else {
                $groups[$key]['commission_unknown_count']++;
            }
        }

        $campaignCodes = array_values(array_filter(array_unique(array_column($groups, 'campaign'))));
        $costByCode = [];
        $currencyByCode = [];
        if ($campaignCodes) {
            foreach (Deeplink::find()->where(['code' => $campaignCodes])->all() as $dl) {
                $costByCode[$dl->code] = (float)$dl->cost;
                $currencyByCode[$dl->code] = $dl->currency;
            }
        }

        $result = [];
        foreach ($groups as $group) {
            $buyers = count($group['buyer_ids']);
            unset($group['buyer_ids']);
            $group['buyers'] = $buyers;

            $group['conversion_rate'] = $group['new_users'] > 0
                ? round($buyers / $group['new_users'] * 100, 2)
                : null;

            $cost = $group['campaign'] ? ($costByCode[$group['campaign']] ?? 0.0) : 0.0;
            $group['cost'] = $cost;
            $group['currency'] = $group['campaign'] ? ($currencyByCode[$group['campaign']] ?? null) : null;

            $group['cac'] = ($cost > 0 && $buyers > 0) ? round($cost / $buyers, 2) : null;
            $group['roas'] = $cost > 0 ? round($group['revenue'] / $cost, 2) : null;

            // Komissiya guruhdagi barcha xaridlar uchun ma'lum bo'lgandagina
            // to'liq hisoblanadi — aks holda taxmin qilinmasdan "qisman
            // noma'lum" deb belgilanadi (commission_unknown_count > 0).
            $group['commission'] = $group['commission_unknown_count'] === 0 ? round($group['commission_known'], 2) : null;
            $group['net_result'] = ($group['commission'] !== null && $cost > 0)
                ? round($group['commission'] - $cost, 2)
                : null;

            $result[] = $group;
        }

        return $result;
    }

    public function exportCsv(array $rows): string
    {
        $headers = [
            'Manba', 'Kampaniya', 'Yangi foydalanuvchi', 'Xaridor', 'Xaridlar soni',
            'Konversiya %', 'Tushum', 'Xarajat', 'Valyuta', 'CAC', 'ROAS', 'Komissiya', 'Sof natija',
        ];

        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $lines[] = implode(',', [
                $this->csvCell($row['source'] ?? '—'),
                $this->csvCell($row['campaign'] ?? '—'),
                $row['new_users'],
                $row['buyers'],
                $row['purchases'],
                $row['conversion_rate'] ?? '',
                $row['revenue'],
                $row['cost'],
                $row['currency'] ?? '',
                $row['cac'] ?? '',
                $row['roas'] ?? '',
                $row['commission'] ?? '',
                $row['net_result'] ?? '',
            ]);
        }

        return implode("\n", $lines);
    }

    private function fetchPurchaseRows(string $from, string $to): array
    {
        return (new Query())
            ->select([
                'user_id' => 'p.user_id',
                'amount' => 'p.amount',
                'first_source' => 'ma.first_source',
                'first_campaign' => 'ma.first_campaign',
                'last_source' => 'ma.last_source',
                'last_campaign' => 'ma.last_campaign',
                'metadata' => 'mt.metadata',
            ])
            ->from(['p' => Police::tableName()])
            ->innerJoin(['ma' => MarketingAttribution::tableName()], 'ma.user_id = p.user_id')
            ->leftJoin(['mt' => MarketingTouch::tableName()], 'mt.police_id = p.id')
            ->where(['p.payment_status' => 1])
            ->andWhere(['between', 'p.created_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->all();
    }

    private function groupKey(?string $source, ?string $campaign): string
    {
        return ($source ?? '') . '|' . ($campaign ?? '');
    }

    private function emptyGroup(?string $source, ?string $campaign): array
    {
        return [
            'source' => $source,
            'campaign' => $campaign,
            'new_users' => 0,
            'buyer_ids' => [],
            'purchases' => 0,
            'revenue' => 0.0,
            'commission_known' => 0.0,
            'commission_unknown_count' => 0,
        ];
    }

    private function csvCell($value): string
    {
        $value = (string)$value;
        if (preg_match('/[",\n]/', $value)) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
