<?php

namespace backend\component;
class CreateOsagoDTO
{
    public bool $success = false;
    public ?string $contractId = null;
    public ?string $policyId = null;
    public ?string $paymentId = null;
    public ?string $paymentLink = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

