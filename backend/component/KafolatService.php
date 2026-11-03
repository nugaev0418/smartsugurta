<?php

namespace backend\component;

use backend\component\insurance\SeasonalInsuranceCatalog;
use backend\kafolat\KafolatClient;
use Yii;

/**
 * EuroAsiaService'ning Kafolat (kafolat.uz) muqobili: avtomobil, egasi, haydovchi
 * va narx hisoblash. Natijalar EAI bilan bir xil DTO'larda qaytadi, shuning uchun
 * bot va WebApp o'zgarishsiz ishlaydi.
 *
 * Kafolat EAI'dan kam maydon beradi: pinfl, districtId, useTerritoryRegionId,
 * vehicleGroupId va alohida ism/familiya yo'q (faqat bitta fullName) — bular
 * DTO'da null qoladi. Haydovchilik guvohnomasini Kafolat o'zi tekshiradi:
 * error.code 636 = shaxs bor, guvohnoma yo'q.
 */
class KafolatService
{
    /** driverInfo: shaxs topildi, lekin haydovchilik guvohnomasi yo'q. */
    public const ERR_NO_LICENSE = 636;

    private const MAX_ATTEMPTS = 3;

    private KafolatClient $client;

    public function __construct(?KafolatClient $client = null)
    {
        $this->client = $client ?? self::defaultClient();
    }

    private static function defaultClient(): KafolatClient
    {
        $cfg = Yii::$app->params['kafolat'] ?? [];

        return new KafolatClient(
            (string)($cfg['baseUrl'] ?? 'https://kafolat.uz'),
            (bool)($cfg['verifySsl'] ?? true)
        );
    }

    public function getVehicleOwnerDTO(string $techSeria, string $techNumber, string $licenseNumber): VehicleOwnerDTO
    {
        $res = $this->call('/api/Osago/vehicle', [
            'govNumber' => $licenseNumber,
            'techPassportSeries' => $techSeria,
            'techPassportNumber' => $techNumber,
        ]);

        if ($res === null || empty($res['body']['success']) || !is_array($res['body']['result'] ?? null)) {
            return new VehicleOwnerDTO(['success' => false]);
        }

        $r = $res['body']['result'];
        $fullName = trim((string)($r['ownerFullName'] ?? ''));
        $isPerson = !empty($r['isIndividual']);

        $data = [
            'success' => true,
            'ownerType' => $isPerson ? 'PERSON' : 'ORGANIZATION',
            'model' => $r['modelName'] ?? null,
        ];

        if ($isPerson) {
            [$first, $last, $middle] = self::splitFullName($fullName);
            $data += ['firstName' => $first, 'lastName' => $last, 'middleName' => $middle];
        } else {
            $data['name'] = $fullName;
        }

        return new VehicleOwnerDTO($data);
    }

    /**
     * Kafolat egasini PINFL emas, avtomobil + pasport bo'yicha qidiradi, shuning uchun
     * $vehicle (govNumber, techSeria, techNumber) majburiy; $pinfl e'tiborga olinmaydi.
     *
     * @param array{govNumber?:string, techSeria?:string, techNumber?:string}|null $vehicle
     */
    public function getPersonByPinflDTO(string $seria, string $number, ?array $vehicle = null): PersonByPinflDTO
    {
        if (empty($vehicle['govNumber']) || empty($vehicle['techSeria']) || empty($vehicle['techNumber'])) {
            Yii::warning('Kafolat egasi: avtomobil ma\'lumoti berilmagan', 'kafolat');
            return new PersonByPinflDTO(['success' => false]);
        }

        $res = $this->call('/api/Osago/vehicle/owner', [
            'govNumber' => $vehicle['govNumber'],
            'techPassportSeries' => $vehicle['techSeria'],
            'techPassportNumber' => $vehicle['techNumber'],
            'passportSeries' => $seria,
            'passportNumber' => $number,
        ]);

        if ($res === null || empty($res['body']['success']) || !is_array($res['body']['result'] ?? null)) {
            return new PersonByPinflDTO(['success' => false]);
        }

        $r = $res['body']['result'];
        [$first, $last, $middle] = self::splitFullName((string)($r['fullName'] ?? ''));

        return new PersonByPinflDTO([
            'success' => true,
            'firstName' => $first,
            'lastName' => $last,
            'middleName' => $middle,
            'birthDate' => self::toEaiDate($r['birthDate'] ?? null),
            'seria' => $r['passportSeries'] ?? $seria,
            'number' => $r['passportNumber'] ?? $number,
        ]);
    }

    public function getPersonByBirthdateDTO(string $seria, string $number, string $birthdateIso): PersonByBirthdateDTO
    {
        $res = $this->call('/api/osago/driverInfo', [
            'birthDate' => substr($birthdateIso, 0, 10),
            'passportSeries' => $seria,
            'passportNumber' => $number,
        ]);

        if ($res === null) {
            return new PersonByBirthdateDTO(['success' => false]);
        }

        $body = $res['body'];

        // Shaxs topildi, haydovchilik guvohnomasi yo'q: success=true, driverLicense=null.
        if (empty($body['success']) && (int)($body['error']['code'] ?? 0) === self::ERR_NO_LICENSE) {
            return new PersonByBirthdateDTO([
                'success' => true,
                'birthDate' => self::toEaiDate($birthdateIso),
                'seria' => $seria,
                'number' => $number,
            ]);
        }

        if (empty($body['success']) || !is_array($body['result'] ?? null)) {
            return new PersonByBirthdateDTO(['success' => false]);
        }

        $r = $body['result'];
        [$first, $last, $middle] = self::splitFullName((string)($r['fullName'] ?? ''));

        return new PersonByBirthdateDTO([
            'success' => true,
            'firstName' => $first,
            'lastName' => $last,
            'middleName' => $middle,
            'birthDate' => self::toEaiDate($r['birthDate'] ?? $birthdateIso),
            'seria' => $r['passportSeries'] ?? $seria,
            'number' => $r['passportNumber'] ?? $number,
            'driverLicense' => true,
        ]);
    }

    /**
     * Narx hisoblash. EAI bilan mos bo'lishi uchun premium TIYINda qaytariladi
     * (chaqiruvchilar `premium / 100` qiladi), Kafolat esa so'mda beradi.
     *
     * @param array{govNumber:string, techSeria:string, techNumber:string} $vehicle
     */
    public function getCalculateOsagoDTO(
        array $vehicle,
        string $seasonalInsuranceId,
        bool $driverRestriction,
        ?string $startDate = null
    ): CalculateOsagoDTO {
        $periodType = null;
        foreach ((new SeasonalInsuranceCatalog())->all() as $season) {
            if ($season['id'] === $seasonalInsuranceId) {
                $periodType = $season['period_type'];
                break;
            }
        }
        if ($periodType === null) {
            return new CalculateOsagoDTO(['success' => false]);
        }

        $res = $this->call('/api/Osago/calculate', [
            'vehicle' => [
                'govNumber' => $vehicle['govNumber'],
                'techPassportSeries' => $vehicle['techSeria'],
                'techPassportNumber' => $vehicle['techNumber'],
            ],
            'seasonalType' => $periodType,
            'isLimited' => $driverRestriction,
            'startDate' => $startDate ?: date('Y-m-d'),
        ]);

        $premium = $res['body']['result']['insurancePremium'] ?? null;
        if ($res === null || empty($res['body']['success']) || !is_numeric($premium)) {
            return new CalculateOsagoDTO(['success' => false]);
        }

        return new CalculateOsagoDTO([
            'success' => true,
            'premium' => (string)((int)round((float)$premium * 100)),
        ]);
    }

    /**
     * Tarmoq/yaroqsiz javob xatolarida MAX_ATTEMPTS gacha qayta urinadi. Biznes
     * javoblari ("topilmadi" va h.k., HTTP 4xx) qayta urinilmaydi. 5xx qayta
     * urinilgach ham xato bo'lsa null qaytadi.
     *
     * @return array{http:int, body:array}|null
     */
    private function call(string $path, array $payload): ?array
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $res = $this->client->post($path, $payload);
            } catch (\Throwable $e) {
                Yii::warning("Kafolat {$path} urinish {$attempt}: " . $e->getMessage(), 'kafolat');
                $res = null;
            }

            if ($res !== null && $res['http'] < 500) {
                return $res;
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                usleep(random_int(300, 800) * 1000);
            }
        }

        return null;
    }

    /** "FAMILIYA ISM OTASINING ISMI [O'G'LI]" -> [ism, familiya, otasining ismi]. */
    public static function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $last = array_shift($parts) ?? '';
        $first = array_shift($parts) ?? '';

        return [$first, $last, implode(' ', $parts)];
    }

    /** Kafolat sanasini ('2026-01-01T00:00:00' yoki 'Y-m-d') EAI formatiga keltiradi. */
    private static function toEaiDate(?string $date): ?string
    {
        if (!$date || !preg_match('/^(\d{4}-\d{2}-\d{2})/', $date, $m)) {
            return null;
        }

        return $m[1] . 'T00:00:00.000Z';
    }
}
