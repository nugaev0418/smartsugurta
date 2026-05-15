<?php

$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

return array_merge([
    'adminEmail' => 'admin@example.com',
    // boshqa umumiy params...
], $localParams);