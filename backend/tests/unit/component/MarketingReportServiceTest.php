<?php

namespace backend\tests\unit\component;

use backend\component\MarketingReportService;

/**
 * exportCsv() — toza funksiya, SQL yo'q, shuning uchun DB ulanishisiz
 * tekshiriladi. (Deeplink::isActive() alohida DB-bog'langan
 * MarketingAttributionServiceTest'da qamrab olinadi — Yii2 ActiveRecord'ning
 * __set() magic metodi hatto bitta propertyni tayinlashda ham
 * getTableSchema() orqali DB sxemasini so'raydi, shu sabab AR obyektini
 * "sof" holda DB'siz sinab bo'lmaydi.)
 */
class MarketingReportServiceTest extends \Codeception\Test\Unit
{
    public function testExportCsvFormatsRowsAndEscapesCommas()
    {
        $service = new MarketingReportService();

        $csv = $service->exportCsv([
            [
                'source' => 'blogger',
                'campaign' => 'dlABC123',
                'new_users' => 10,
                'buyers' => 3,
                'purchases' => 4,
                'conversion_rate' => 30.0,
                'revenue' => 400000,
                'cost' => 100000,
                'currency' => 'UZS',
                'cac' => 33333.33,
                'roas' => 4.0,
                'commission' => 20000,
                'net_result' => -80000,
            ],
            [
                'source' => null,
                'campaign' => null,
                'new_users' => 5,
                'buyers' => 0,
                'purchases' => 0,
                'conversion_rate' => null,
                'revenue' => 0,
                'cost' => 0,
                'currency' => null,
                'cac' => null,
                'roas' => null,
                'commission' => null,
                'net_result' => null,
            ],
        ]);

        $lines = explode("\n", $csv);
        $this->assertCount(3, $lines); // header + 2 rows
        $this->assertStringContainsString('dlABC123', $lines[1]);
        $this->assertStringContainsString('30', $lines[1]);
        $this->assertStringContainsString('—,—', $lines[2]);
    }
}
