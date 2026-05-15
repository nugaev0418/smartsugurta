<?php

namespace backend\queue;

use backend\gross\GrossOsago;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class GrossOsagoJob extends BaseObject implements JobInterface
{
    /** @var array $policyData full.php dagi $policy_data strukturasiga mos keladi */
    public array $policyData = [];

    public function __construct(array $data)
    {
        $this->policyData = $data;
    }

    public function execute($queue): void
    {
        $grossCfg = Yii::$app->params['gross'];

        $service = new GrossOsago([
            'login'        => $grossCfg['login'],
            'password'     => $grossCfg['password'],
            'sender_pinfl' => $grossCfg['senderPinfl'],
            'marka_id'     => $grossCfg['markaId'] ?? 13,
            'openai_key'   => Yii::$app->params['openai']['apiKey'],
            'response_dir' => isset($grossCfg['responseDir'])
                ? Yii::getAlias($grossCfg['responseDir'])
                : Yii::getAlias('@runtime/gross'),
        ]);

        $result = $service->run($this->policyData);


        var_dump($result);

        Yii::info(sprintf(
            'OSAGO created | anketa: %s | premium: %s so\'m | click: %s | payme: %s',
            $result['anketa_id'],
            number_format($result['premium']),
            $result['click_url'] ?? '-',
            $result['payme_url'] ?? '-',
        ), 'gross.osago');
    }
}