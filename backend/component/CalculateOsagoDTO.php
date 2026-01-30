<?php

namespace backend\component;
class CalculateOsagoDTO
{
    public bool $success = false;
    public ?string $premium = null;
    public ?string $sum = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

