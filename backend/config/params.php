<?php

$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

return array_merge([
    'adminEmail' => 'admin@example.com',
    // boshqa umumiy params...
    'osago' => [
        // Toshkent (01/10) davlat raqamlarini to'g'ridan-to'g'ri EuroAsia'ga yuborish —
        // hozircha o'chirilgan: bot hech qachon bu yo'lni ishlatmagan (eski kodda
        // "if (true)" bilan doim Gross navbatiga yuborilardi), shuning uchun ishga
        // tushirishdan oldin staging'da alohida sinovdan o'tkazish kerak. WebApp ham
        // endi shu qarorga bo'ysunadi (Toshkent raqamlari ham har doim Gross'ga
        // boradi) — ilgari WebApp bu bayroqdan qat'i nazar har doim to'g'ridan-to'g'ri
        // EAI'ga borardi, bu endi kerak emas deb qaror qilindi.
        'enableDirectEaiForTashkent' => false,
    ],
], $localParams);