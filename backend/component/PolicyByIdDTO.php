<?php

namespace backend\component;
class policyByIdDTO
{
    public bool $success = false;
    public ?string $pdfUrl = null;
    public ?string $status = null;
    public ?string $productName = null;
    public ?string $paymentId = null;
    public ?string $paymentStatus = null;
    public ?string $amount = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

