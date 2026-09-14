<?php

namespace backend\tests\unit\component\bot;

use backend\component\bot\WebhookGuard;
use Yii;

/**
 * Uses FileMutex instead of production's MysqlMutex (no DB reachable in
 * this suite) — exercises the same acquire/release contract WebhookGuard
 * relies on, just via a different backend.
 */
class WebhookGuardTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Yii::$app->set('mutex', ['class' => \yii\mutex\FileMutex::class]);
    }

    public function testFirstUpdateIsNotADuplicate()
    {
        $guard = new WebhookGuard();
        $this->assertFalse($guard->isDuplicateUpdate(random_int(1, PHP_INT_MAX)));
    }

    public function testSameUpdateIdSeenTwiceIsADuplicate()
    {
        $guard = new WebhookGuard();
        $updateId = random_int(1, PHP_INT_MAX);

        $this->assertFalse($guard->isDuplicateUpdate($updateId));
        $this->assertTrue($guard->isDuplicateUpdate($updateId));
    }

    public function testFalsyUpdateIdIsNeverADuplicate()
    {
        $guard = new WebhookGuard();
        $this->assertFalse($guard->isDuplicateUpdate(0));
        $this->assertFalse($guard->isDuplicateUpdate(null));
    }

    public function testNonNumericChatIdCannotBeLocked()
    {
        $guard = new WebhookGuard();
        $this->assertNull($guard->tryLock('abc'));
    }

    public function testNumericChatIdLocksAndUnlocks()
    {
        $guard = new WebhookGuard();
        $chatId = (string) random_int(1, PHP_INT_MAX);

        $lockKey = $guard->tryLock($chatId);
        $this->assertNotNull($lockKey);

        // A second concurrent attempt for the same chat fails while locked.
        $this->assertNull($guard->tryLock($chatId));

        $guard->unlock($lockKey);

        // Free again after unlock.
        $this->assertNotNull($guard->tryLock($chatId));
    }
}
