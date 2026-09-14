<?php

namespace backend\component\bot;

use Yii;

/**
 * Extracted verbatim from BotController::actionStart(): the Telegram
 * update-retry dedupe check and the per-chat mutex lock that stops two
 * webhook deliveries for the same user being processed concurrently.
 */
class WebhookGuard
{
    /**
     * Telegram redelivers an update when our webhook response was slow —
     * such retries must not be reprocessed.
     */
    public function isDuplicateUpdate($updateId): bool
    {
        return (bool) $updateId && !Yii::$app->cache->add("tg_update_{$updateId}", 1, 120);
    }

    /**
     * Returns the lock key on success, or null when the chat id isn't
     * numeric (nothing to lock) or the lock is already held by another
     * in-flight update for the same chat. Callers must still check
     * is_numeric($chatId) themselves to tell those two null cases apart —
     * matching the original inline logic exactly.
     */
    public function tryLock(string $chatId): ?string
    {
        if (!is_numeric($chatId)) {
            return null;
        }

        $lockKey = "bot_busy_{$chatId}";

        return Yii::$app->mutex->acquire($lockKey, 0) ? $lockKey : null;
    }

    public function unlock(string $lockKey): void
    {
        Yii::$app->mutex->release($lockKey);
    }
}
