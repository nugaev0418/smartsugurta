<?php

namespace backend\controllers;

use backend\component\EuroAsiaService;
use backend\queue\PaynetQueue;
use common\models\Payment;
use common\models\Police;
use Yii;
use yii\web\Controller;

class TestController extends Controller
{
    public function actionIndex()
    {

        $data = [
            'user_id' => 4,
            'payment_order_id' => 6,
            'account_number' => '979100553',
            'amount' => 1000,
            'payment_type' => Payment::TO_PHONE,
            'paynet_id' => 3,
        ];

        $queue_name = "paynetQueue";
        Yii::$app->$queue_name->delay(1)->push(new PaynetQueue($data));


    }

    public function findCompany($number)
    {
        $merchents = [
            '549981c05ae5eca82d1b4661' => [97, 88],      //"Mobiuz",
            '545e1b1e5ae5eca82d1b4630' => [93, 94, 50], //"Ucell",
            '545c7ecd5ae5eca82d1b462f' => [90, 91],      //"Beeline",
            '55478199d2c4830936e6c832' => [77, 95, 99], //"UZmobile",
            '545e1cae5ae5eca82d1b4631' => [98],         //"Perfectum",
            '671b49fa79fa21d81475cbec' => [33],         //"Humans",
        ];
        $prefix = substr($number, 0, 2);

        foreach ($merchents as $merchent => $codes) {
            if (in_array($prefix, $codes)) {
                return $merchent;
            }
        }
        return false;
    }
}