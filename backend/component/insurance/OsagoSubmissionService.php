<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\queue\GrossOsagoJob;
use common\models\Botuser;
use common\models\Police;
use common\models\SeasonalInsurance;
use Yii;

/**
 * Decides whether an OSAGO application goes straight to EuroAsia or through
 * the Gross Insurance queue, and does the corresponding side effect (save a
 * Police record, or push a GrossOsagoJob). Shared by BotController's
 * ConfirmStageHandler and WebAppController::actionSubmit() so the branch
 * and the Police-creation shape are written once.
 *
 * $allowDirectEaiForTashkent is an explicit parameter, not a value this
 * class reads for itself. Both current callers (BotController's
 * ConfirmStageHandler and WebAppController::actionSubmit()) pass false —
 * all submissions, Tashkent plates included, go through the Gross queue.
 * WebApp used to pass true for a Tashkent-plate direct-EAI path, but that
 * behavior is no longer wanted (see params.php
 * 'osago.enableDirectEaiForTashkent'). The parameter stays explicit rather
 * than being read internally so a future caller can still opt back in
 * independently, once EAI is reliable again.
 */
class OsagoSubmissionService
{
    public function __construct(
        private OsagoRequestBuilder $builder,
        private EuroAsiaService $euroAsia
    ) {
    }

    public function submit(OsagoApplicationData $data, Botuser $botuser, bool $allowDirectEaiForTashkent): OsagoSubmissionResult
    {
        $eaiPayload = $this->builder->buildEaiPayload($data);

        if (!$allowDirectEaiForTashkent || !$this->builder->isTashkentPlate($data->plateNumber)) {
            $grossPayload = $this->builder->buildGrossPayload($data);

            $jobId = Yii::$app->grossQueue->push(new GrossOsagoJob([
                'policyDataGross' => $grossPayload,
                'policyDataEAI' => $eaiPayload,
                'chat_id' => $botuser->chat_id,
            ]));

            return OsagoSubmissionResult::gross($jobId);
        }

        $dto = $this->euroAsia->createOsagoDTO($eaiPayload);
        if (!$dto->success) {
            return OsagoSubmissionResult::failed();
        }

        $seasonModel = SeasonalInsurance::find()->where(['seasonId' => $data->seasonalInsuranceId])->one();

        $police = new Police();
        $police->policeId = $dto->policyId;
        $police->user_id = $botuser->id;
        $police->startAt = substr($data->startAtIso, 0, 10);
        $police->paymentLink = $dto->paymentLink;
        $police->paymentId = $dto->paymentId;
        $police->gateway = $data->gateway;
        $police->amount = 0;
        $police->driverRestriction = $data->driverRestriction ? 1 : 0;
        $police->season_id = $seasonModel->id ?? null;
        $police->provider_id = Police::PROVIDER_EAI;
        $police->save(false);

        return OsagoSubmissionResult::eai($police, $dto->paymentLink);
    }
}
