<?php
namespace backend\models;
use Yii;
use yii\base\ErrorException;

class PaynetAPI2
{
    public function payPhone($phoneNumber, $amount, $paynetId)
    {
        $curl = curl_init();
        $data = [
            'phoneNumber' => $phoneNumber,
            'amount' => $amount,
            'paynetId' => $paynetId,
            'secret' => 'sarmin'
        ];


        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kjksjdkls.nugaev.uz/pay5sd4fs5df41/phone',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);

        $response = json_decode($response, true);

        if (isset($response['status']) && $response['status'] == true) {
            return [
                'status' => true,
            ];
        }
        return [
            'status' => false,
            'data' => $response,
        ];
    }

    public function payCard($cardNumber, $amount, $paynetId)
    {
        $curl = curl_init();
        $data = [
            'cardNumber' => $cardNumber,
            'amount' => $amount,
            'paynetId' => $paynetId,
            'secret' => 'sarmin'
        ];


        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kjksjdkls.nugaev.uz/pay5sd4fs5df41/card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);

        $response = json_decode($response, true);


        if (isset($response['status']) && $response['status'] == true) {
            return [
                'status' => true,
            ];
        }
        return [
            'status' => false,
            'data' => $response,
        ];
    }

}