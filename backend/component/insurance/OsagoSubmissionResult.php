<?php

namespace backend\component\insurance;

use common\models\Police;

/**
 * Outcome of OsagoSubmissionService::submit(): which path was taken and
 * whatever the caller needs to report it (Gross: the queue push's return
 * value, for the same admin-audit log BotController always sent; EAI: the
 * saved Police record + payment link; failure: nothing beyond the mode).
 */
class OsagoSubmissionResult
{
    private function __construct(
        public readonly string $mode,
        public readonly ?Police $police = null,
        public readonly ?string $paymentLink = null,
        public readonly mixed $grossJobId = null
    ) {
    }

    public static function gross(mixed $jobId): self
    {
        return new self('gross', null, null, $jobId);
    }

    public static function eai(Police $police, string $paymentLink): self
    {
        return new self('eai', $police, $paymentLink);
    }

    public static function failed(): self
    {
        return new self('failed');
    }
}
