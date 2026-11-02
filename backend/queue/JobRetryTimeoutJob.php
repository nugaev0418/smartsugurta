<?php

namespace backend\queue;

use backend\component\insurance\JobRetryService;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Admin qayta urinish taklifiga javob bermasa (JobRetryService::TIMEOUT_SECONDS), foydalanuvchiga
 * uzilish xabarini yuboradi. Ish idempotent: taklif allaqachon hal qilingan bo'lsa hech narsa qilmaydi.
 */
class JobRetryTimeoutJob extends BaseObject implements JobInterface
{
    public int $retryId = 0;

    public function execute($queue): void
    {
        (new JobRetryService())->expire($this->retryId);
    }
}
