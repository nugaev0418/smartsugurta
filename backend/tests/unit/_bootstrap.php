<?php
/**
 * Here you can initialize variables via \Codeception\Util\Fixtures class
 * to store data in global array and use it in Tests.
 *
 * ```php
 * // Here _bootstrap.php
 * \Codeception\Util\Fixtures::add('user1', ['name' => 'davert']);
 * ```
 *
 * In Tests
 *
 * ```php
 * \Codeception\Util\Fixtures::get('user1');
 * ```
 */

// The unit suite has no Yii2 module (no DB), so nothing normally creates a
// Yii application here. A minimal console app — no 'db' component — is
// enough for tests that only need Yii::createObject()/Yii::$app->params/
// Yii::$app->cache and don't touch ActiveRecord. Tests that do need a real
// database (BotUserState, full FSM flow, OsagoSubmissionService's EAI/Police
// path) must be run where the project's DB is reachable — see each such
// test's own docblock.
if (!\Yii::$app) {
    new \yii\console\Application([
        'id' => 'app-backend-test',
        'basePath' => dirname(__DIR__, 2),
        'components' => [
            'cache' => ['class' => \yii\caching\FileCache::class],
        ],
        'params' => require dirname(__DIR__, 2) . '/config/params.php',
    ]);
}
