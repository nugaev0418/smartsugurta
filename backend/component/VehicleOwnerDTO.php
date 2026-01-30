<?php

namespace backend\component;
class VehicleOwnerDTO
{
    public bool $success = false;
    public string $ownerType = 'UNKNOWN';

    public ?string $inn = null;
    public ?string $name = null;

    public ?string $firstName = null;
    public ?string $middleName = null;
    public ?string $lastName = null;
    public ?string $pinfl = null;
    public ?string $birthDate = null;

    public ?string $useTerritoryRegionId = null;
    public ?string $vehicleGroupId = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

