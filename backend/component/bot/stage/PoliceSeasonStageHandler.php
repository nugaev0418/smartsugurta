<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\insurance\SeasonalInsuranceCatalog;
use backend\models\EuroAsia;
use backend\models\Pages;
use DateTime;

/**
 * Pages::POLICE_SEASON_TYPE, START_AT, PAYMENT_TYPE — extracted verbatim
 * from BotController::showPoliceSeasonPage()/handlePoliceSeasonPage()/
 * showStartAtPage()/handleStartAtPage()/showPaymentTypePage()/
 * handlePaymentTypePage()/extractValidDate().
 */
class PoliceSeasonStageHandler implements BotStageInterface
{
    // Text-keyword -> SeasonalInsuranceCatalog key. The catalog keys ('1y'/'6m'/'20d')
    // match WebAppController::SEASONS' own keys; only the presentation-side keyword is bot-specific.
    private const SEASON_CATALOG_KEY_BY_KEYWORD = [
        '1 year' => '1y',
        '6 months' => '6m',
        '20 days' => '20d',
    ];

    public function __construct(
        private ConfirmStageHandler $confirmStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::POLICE_SEASON_TYPE;
        switch ($page) {
            case Pages::START_AT:
                $this->showStartAt($ctx);
                break;
            case Pages::PAYMENT_TYPE:
                $this->showPaymentType($ctx);
                break;
            default:
                $this->showPoliceSeason($ctx);
        }
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::START_AT:
                $this->handleStartAt($ctx);
                break;
            case Pages::PAYMENT_TYPE:
                $this->handlePaymentType($ctx);
                break;
            case Pages::POLICE_SEASON_TYPE:
                $this->handlePoliceSeason($ctx);
                break;
        }
    }

    public function showPoliceSeason(BotContext $ctx): void
    {
        $ctx->page = Pages::POLICE_SEASON_TYPE;

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText('1 year')),
                $ctx->telegram->buildKeyboardButton($ctx->getMText('6 months')),
                $ctx->telegram->buildKeyboardButton($ctx->getMText('20 days')),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($ctx->getMText('Choose season police'), $option);
    }

    public function handlePoliceSeason(BotContext $ctx): void
    {
        $message = $ctx->getKeywordText($ctx->text);
        $seasonKey = self::SEASON_CATALOG_KEY_BY_KEYWORD[$message] ?? null;
        if ($seasonKey !== null){

            $season = (new SeasonalInsuranceCatalog())->byKey($seasonKey);
            $ctx->policeSeason = $season;

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['period_type'] = $season['period_type'];
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

            $this->showStartAt($ctx);
        }else{
            $this->showPoliceSeason($ctx);
        }
    }

    public function showStartAt(BotContext $ctx): void
    {
        $ctx->page = Pages::START_AT;

        $today = date("d.m.Y");

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($today),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];

        $text = sprintf($ctx->getMText("start at text"), $today);
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleStartAt(BotContext $ctx): void
    {
        if (empty($ctx->text)) {
            $this->showStartAt($ctx);
            return;
        }

        $startDate = $this->extractValidDate($ctx->text);

        if (!isset($startDate['success']) || !$startDate['success']) {
            $this->showStartAt($ctx);
            return;
        }

        $ctx->startAt = $startDate['date'];

        $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
        $police_data['start_date'] = DateTime::createFromFormat(
            'd.m.Y',
            $ctx->startAt
        )->format('Y-m-d');

        $ctx->police_data = $police_data;
        $ctx->sendMessageAdmin(json_encode($police_data));

        $this->showPaymentType($ctx);
    }

    public function showPaymentType(BotContext $ctx): void
    {
        $ctx->page = Pages::PAYMENT_TYPE;

        $option = [
            [
                $ctx->telegram->buildKeyboardButton(EuroAsia::GATEWAY_CLICK),
                $ctx->telegram->buildKeyboardButton(EuroAsia::GATEWAY_PAYME),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($ctx->getMText('Choose a payment method'), $option);
    }

    public function handlePaymentType(BotContext $ctx): void
    {
        switch ($ctx->text) {
            case EuroAsia::GATEWAY_PAYME:
            case EuroAsia::GATEWAY_CLICK:
                $ctx->paymentType = $ctx->text;
                $this->confirmStage->show($ctx);
                break;
            default:
                $this->showPaymentType($ctx);
        }
    }

    private function extractValidDate(string $text): array
    {
        // 1️⃣ Sanani topamiz
        if (!preg_match('/(\d{2}[\s.,]\d{2}[\s.,]\d{4}|\d{8})/', $text, $m)) {
            return ['success' => false, 'error' => 'Date not found'];
        }

        // 2️⃣ Faqat raqam qoldiramiz
        $raw = preg_replace('/\D/', '', $m[0]); // masalan: 11122025

        if (strlen($raw) !== 8) {
            return ['success' => false, 'error' => 'Invalid date length'];
        }

        $day   = (int)substr($raw, 0, 2);
        $month = (int)substr($raw, 2, 2);
        $year  = (int)substr($raw, 4, 4);

        // 3️⃣ Sana realmi?
        if (!checkdate($month, $day, $year)) {
            return ['success' => false, 'error' => 'Invalid calendar date'];
        }

        $date = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
        $date->setTime(0, 0, 0);

        // 4️⃣ Bugungi sana
        $today = new DateTime('today');

        if ($date < $today) {
            return ['success' => false, 'error' => 'Date is in the past'];
        }

        return [
            'success' => true,
            'date'    => $date->format('d.m.Y'),
        ];
    }
}
