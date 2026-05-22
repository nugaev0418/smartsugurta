<?php

namespace console\controllers;

use backend\queue\GrossOsagoJob;
use Yii;

class GrossController extends \yii\console\Controller
{
    public function actionAdd()
    {
        $police_data = [

        // Transport
        'vehicle' => [
            'seria'      => 'AAF',        // Tex passport seria
            'number'     => '2998242',    // Tex passport raqam
            'gov_number' => '40011VBA',   // Davlat raqami
        ],

        // Egasi
        'owner' => [
            'is_org'   => true,          // false = jismoniy shaxs, true = yuridik tashkilot
            'passport' => 'AD6970989',    // Faqat jismoniy shaxs uchun (is_org=false)
        ],

        // Polisa
        'policy_type' => 'limited',       // 'limited' (cheklangan) | 'unlimited' (cheklanmagan)
        'period_type' => 8,               // 1 = 6 oy  |  7 = 1 yil  |  8 = 20 kun
        'start_date' => '2026-05-20',

        // Kontakt telefon +998 qo'shmaysan 7 ta raqam kiritilishi kerak.
        'phone' => '979100553',

        // Haydovchilar — faqat policy_type='limited' bo'lsa to'ldiring
        // policy_type='unlimited' bo'lsa bo'sh qoldiring: []
        'drivers' => [
            [
                'document'      => 'AB4112696',   // Passport seriya+raqam
                'birth_date'    => '1992-07-25',   // Tug'ilgan sana YYYY-MM-DD
                'relative_type' => 0,              // 0=qarindosh emas, 1=ota, 2=ona, 3=er,
                // 4=xotin, 5=o'gil, 6=qiz, 7=aka,
                // 8=uka, 9=opa, 10=singlisi
            ],
            [
                'document'      => 'AD8785312',   // Passport seriya+raqam
                'birth_date'    => '1991-01-21',   // Tug'ilgan sana YYYY-MM-DD
                'relative_type' => 0,              // 0=qarindosh emas, 1=ota, 2=ona, 3=er,
                // 4=xotin, 5=o'gil, 6=qiz, 7=aka,
                // 8=uka, 9=opa, 10=singlisi
            ],
        ],

        ];
        $result = Yii::$app->grossQueue->push(new GrossOsagoJob(
            $police_data
        ));

        var_dump($result);
    }
}