<?php

namespace backend\component;
class PersonByBirthdateDTO
{
    public bool $success = false;
    public ?string $firstName = null;
    public ?string $middleName = null;
    public ?string $lastName = null;
    public ?string $pinfl = null;
    public ?string $birthDate = null;
    public ?string $seria = null;
    public ?string $number = null;
    public ?string $districtId = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

