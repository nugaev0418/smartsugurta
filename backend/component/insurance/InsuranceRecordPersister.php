<?php

namespace backend\component\insurance;

use backend\component\RelativeType;
use common\models\Driver;
use common\models\Owner;
use common\models\Police;
use common\models\Relative;
use common\models\Vehicle;

/**
 * Sotuv tasdiqlangandan (Police yaratilgandan) keyin OsagoApplicationData'da
 * vaqtinchalik yashagan avtomobil/egasi/haydovchilar ma'lumotini lokal
 * jadvallarga (vehicle/owner/driver, police_id orqali bog'langan) yozadi.
 * Ilgari bu ma'lumot faqat provayderga yuborilgan, lokal saqlanmagan edi.
 *
 * $applicationData — OsagoApplicationData'ning public propertylaridan
 * (array) cast qilingan massiv — GrossOsagoJob queue orqali serialize
 * qilingani uchun typed obyekt emas, oddiy massiv qabul qilinadi.
 *
 * Idempotent: agar shu $police->id uchun Vehicle qatori allaqachon mavjud
 * bo'lsa, hech narsa qayta yozilmaydi — GrossOsagoJob::tryGross() xatolik
 * yuzasidan (masalan Telegram'ga xabar yuborishda) qayta urinilganda ham
 * (bu holatda Police allaqachon saqlangan bo'ladi) bir xil ma'lumot
 * takrorlanib yozilmasligi uchun.
 */
class InsuranceRecordPersister
{
    public function persist(Police $police, array $applicationData): void
    {
        if (Vehicle::find()->where(['police_id' => $police->id])->exists()) {
            return;
        }

        $this->persistVehicle($police, $applicationData);
        $this->persistOwner($police, $applicationData);
        $this->persistDrivers($police, $applicationData);
    }

    private function persistVehicle(Police $police, array $data): void
    {
        $vehicle = new Vehicle();
        $vehicle->licenseNumber = $data['plateNumber'] ?? null;
        $vehicle->techPassportSeria = $data['techSeria'] ?? null;
        $vehicle->techPassportNumber = $data['techNumber'] ?? null;
        $vehicle->police_id = $police->id;
        $vehicle->save(false);
    }

    private function persistOwner(Police $police, array $data): void
    {
        $owner = new Owner();
        $owner->police_id = $police->id;

        if (!empty($data['isOrganization'])) {
            $owner->type = 'ORGANIZATION';
            $owner->inn = $data['organizationInn'] ?? null;
            $owner->name = $data['organizationName'] ?? null;
        } else {
            $owner->type = 'PERSON';
            $owner->firstName = $data['ownerFirstName'] ?? null;
            $owner->middlename = $data['ownerMiddleName'] ?? null;
            $owner->lastname = $data['ownerLastName'] ?? null;
            $owner->pinfl = $data['ownerPinfl'] ?? null;
            $owner->districtId = $data['districtId'] ?? null;
        }

        $owner->save(false);
    }

    private function persistDrivers(Police $police, array $data): void
    {
        foreach (($data['drivers'] ?? []) as $driverData) {
            $driver = new Driver();
            $driver->police_id = $police->id;
            $driver->passportSeria = $driverData['seria'] ?? null;
            $driver->passportNumber = $driverData['number'] ?? null;
            $driver->passportBirthdate = isset($driverData['birthDateIso'])
                ? substr($driverData['birthDateIso'], 0, 10)
                : null;
            $driver->firstName = $driverData['firstName'] ?? null;
            $driver->lastName = $driverData['lastName'] ?? null;
            $driver->relativeId = $this->resolveRelativeId((int)($driverData['relativeType'] ?? 0));
            $driver->save(false);
        }
    }

    private function resolveRelativeId(int $relativeType): ?int
    {
        $id = Relative::find()
            ->select('id')
            ->where(['relativeId' => RelativeType::eaiId($relativeType)])
            ->scalar();

        return $id ? (int)$id : null;
    }
}
