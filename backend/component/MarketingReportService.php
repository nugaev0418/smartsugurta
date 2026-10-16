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
            ->select([
                'source' => $sourceCol,
                'campaign' => $campaignCol,
                'survey_source' => 'survey_source',
                'new_users' => 'COUNT(*)',
            ])
            ->from(MarketingAttribution::tableName())
            ->where(['between', 'first_seen_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy([$sourceCol, $campaignCol, 'survey_source'])
            ->all();

        $purchaseRows = $this->fetchPurchaseRows($from, $to);

        $groups = [];
        foreach ($newUsersRows as $row) {
            $source = $this->resolveEffectiveSource($row['source'], $row['survey_source']);
            $key = $this->groupKey($source, $row['campaign']);
            $groups[$key] ??= $this->emptyGroup($source, $row['campaign']);
            $groups[$key]['new_users'] += (int)$row['new_users'];
        }

        foreach ($purchaseRows as $row) {
            $rawSource = $touchType === 'last' ? $row['last_source'] : $row['first_source'];
            $campaign = $touchType === 'last' ? $row['last_campaign'] : $row['first_campaign'];
            $source = $this->resolveEffectiveSource($rawSource, $row['survey_source']);
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
        $nameByCode = [];
        if ($campaignCodes) {
            foreach (Deeplink::find()->where(['code' => $campaignCodes])->all() as $dl) {
                $costByCode[$dl->code] = (float)$dl->cost;
                $currencyByCode[$dl->code] = $dl->currency;
                $nameByCode[$dl->code] = $dl->name;
            }
        }

        $result = [];
        foreach ($groups as $group) {
            $buyers = count($group['buyer_ids']);
            unset($group['buyer_ids']);
            $group['buyers'] = $buyers;
            $group['source_label'] = $this->sourceLabel($group['source'], $group['campaign'], $nameByCode);

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

    /**
     * So'rovnomaga javob berganlarning javoblari bo'yicha taqsimoti —
     * dashboardda "To'g'ridan-to'g'ri" deb umumlashtirilgan foydalanuvchilar
     * aslida qayerdan kelganini ko'rsatadi.
     */
    public function getSurveyBreakdown(string $from, string $to): array
    {
        $rows = (new Query())
            ->select(['survey_source', 'cnt' => 'COUNT(*)'])
            ->from(MarketingAttribution::tableName())
            ->where(['between', 'first_seen_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->andWhere(['not', ['survey_source' => null]])
            ->groupBy('survey_source')
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'key' => $row['survey_source'],
                'label' => MarketingAttribution::SURVEY_LABELS[$row['survey_source']] ?? $row['survey_source'],
                'count' => (int)$row['cnt'],
            ];
        }

        usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

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
                $this->csvCell($row['source_label'] ?? $row['source'] ?? '—'),
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
                'survey_source' => 'ma.survey_source',
                'metadata' => 'mt.metadata',
            ])
            ->from(['p' => Police::tableName()])
            ->innerJoin(['ma' => MarketingAttribution::tableName()], 'ma.user_id = p.user_id')
            ->leftJoin(['mt' => MarketingTouch::tableName()], 'mt.police_id = p.id')
            ->where(['p.payment_status' => 1])
            ->andWhere(['between', 'p.created_at', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->all();
    }

    /**
     * source_method='direct' (ya'ni manba aniqlanmagan) bo'lgan
     * foydalanuvchi so'rovnomaga javob bergan bo'lsa, hisobotda xom
     * "direct"/"unknown" o'rniga shu javob asosiy manba sifatida
     * ko'rsatiladi — aks holda so'rovnoma javobi hech qayerda
     * ko'rinmay qolardi.
     */
    private function resolveEffectiveSource(?string $source, ?string $surveySource): ?string
    {
        if ($surveySource && in_array($source, [null, '', MarketingAttribution::METHOD_DIRECT, 'unknown'], true)) {
            return 'survey:' . $surveySource;
        }
        return $source;
    }

    /**
     * $campaign (deeplink.code) ma'lum bo'lsa — kampaniyaning o'z nomi
     * (masalan "Blogger Jasur") eng foydali va aniq label, shuning uchun
     * manba-bucket (channel/source bo'sh bo'lsa ham) ustidan ustuvor.
     * Faqat $campaign bo'lmaganda (yoki nomi topilmaganda) umumiy
     * manba-bucket'lar (direct/referral/survey/unknown) ko'rsatiladi.
     */
    private function sourceLabel(?string $source, ?string $campaign, array $nameByCode): string
    {
        if ($campaign && !empty($nameByCode[$campaign])) {
            return $nameByCode[$campaign];
        }

        if ($source === null || $source === '') {
            return 'Noma\'lum';
        }
        if ($source === MarketingAttribution::METHOD_DIRECT) {
            return "To'g'ridan-to'g'ri (hal qilinmagan)";
        }
        if ($source === MarketingAttributionService::SOURCE_CAMPAIGN_UNSPECIFIED) {
            return $campaign ? "Kampaniya ({$campaign}) — kanal belgilanmagan" : 'Kampaniya (kanal belgilanmagan)';
        }
        if ($source === 'unknown') {
            return "Noma'lum (yaroqsiz havola)";
        }
        if ($source === 'referral') {
            return 'Referral (do\'stdan)';
        }
        if (str_starts_with($source, 'survey:')) {
            $key = substr($source, 7);
            return "So'rovnoma: " . (MarketingAttribution::SURVEY_LABELS[$key] ?? $key);
        }

        return Deeplink::getSourceList()[$source] ?? $source;
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
