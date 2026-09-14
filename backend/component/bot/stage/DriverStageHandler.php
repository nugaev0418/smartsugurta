<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\insurance\DriverLookupService;
use backend\component\insurance\PassportTextParser;
use backend\component\RelativeType;
use backend\controllers\BotController;
use backend\models\Pages;

/**
 * Pages::DRIVER_PAGE, RELATIVE_PAGE — extracted verbatim from
 * BotController::showDriverPage()/handleDriverPage()/showRelativePage()/
 * handleRelativePage()/finalizeDriver().
 */
class DriverStageHandler implements BotStageInterface
{
    // Button-label -> canonical Gross relative_type code (0-10), matching the
    // scheme documented in finalizeDriver() and shared with EuroAsia via
    // RelativeType::eaiId(). Keyed on getKeywordText()'s language-independent
    // keyword, same as showRelative()'s buttons.
    private const RELATIVE_CODE_BY_KEYWORD = [
        'Not related' => RelativeType::NOT_RELATED,
        'Father' => RelativeType::FATHER,
        'Mother' => RelativeType::MOTHER,
        'Husband' => RelativeType::HUSBAND,
        'Wife' => RelativeType::WIFE,
        'Son' => RelativeType::SON,
        'Girl' => RelativeType::DAUGHTER,
        'Big brother' => RelativeType::OLDER_BROTHER,
        'Little brother' => RelativeType::YOUNGER_BROTHER,
        'Big sister' => RelativeType::OLDER_SISTER,
        'Little sister' => RelativeType::YOUNGER_SISTER,
    ];

    public function __construct(
        private PoliceSeasonStageHandler $policeSeasonStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::DRIVER_PAGE;
        if ($page === Pages::RELATIVE_PAGE) {
            $this->showRelative($ctx);
            return;
        }

        $this->showDriver($ctx, $params['no_driver_button'] ?? false, $params['message'] ?? false);
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::RELATIVE_PAGE:
                $this->handleRelative($ctx);
                break;
            case Pages::DRIVER_PAGE:
                $this->handleDriver($ctx);
                break;
        }
    }

    public function showDriver(BotContext $ctx, $no_driver_button = false, $message = false): void
    {
        $ctx->page = Pages::DRIVER_PAGE;

        $text = $message != false ? $message : $ctx->getMText("ask driver");

        $option = [];
        if($no_driver_button){
            $option[] = [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("No other drivers")),
            ];
        }

        $option[] = [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleDriver(BotContext $ctx, $driver_data = null): void
    {
        if (!is_null($driver_data)){
            $ctx->text = $driver_data;
        }
        if (!is_null($ctx->text) && $ctx->getKeywordText($ctx->text) != 'No other drivers'){

            $passData = (new PassportTextParser())->parse($ctx->text);
            $drivers = $ctx->drivers != '' ? $ctx->drivers : [];
            if ($passData['success']){
                $seria = $passData['series'];
                $number = $passData['number'];
                $birthdate = BotController::toIsoDate($passData['birth']);

                $dto = (new DriverLookupService())->lookup($seria, $number, $birthdate);
                $ctx->logApiCall('DriverLookupService::lookup', [
                    'seria' => $seria,
                    'number' => $number,
                    'birthdate' => $birthdate,
                ], $dto);

                if (!$dto->success){
                    $ctx->sendMessage($ctx->getMText('Driver found transport'));
                }elseif (!$dto->driverLicense){
                    $ctx->sendMessage($ctx->getMText("This driver's driver's license was not found."));
                }else{

                    $ctx->pendingDriver = [
                        'seria' => $seria,
                        'number' => $number,
                        'birthdate' => $birthdate,
                        'firstName' => $dto->firstName,
                        'lastName' => $dto->lastName,
                    ];

                    if (!is_null($driver_data)) {
                        // Owner added as their own driver (handleOwnerIsDriverPage) — no
                        // relation prompt, defaults to "not related" like the web app's "O'zi".
                        $this->finalizeDriver($ctx, RelativeType::NOT_RELATED);
                    } else {
                        $this->showRelative($ctx);
                    }
                }
            }else{
                $this->showDriver($ctx, count($drivers));
            }

        } else {
            $this->policeSeasonStage->showPoliceSeason($ctx);
        }
    }

    public function showRelative(BotContext $ctx): void
    {
        $ctx->page = Pages::RELATIVE_PAGE;

        $text = $ctx->getMText("Indicate the driver's relationship.");
        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Not related")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Father")),
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Mother")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Husband")),
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Wife")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Son")),
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Girl")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Big brother")),
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Little brother")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Big sister")),
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Little sister")),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleRelative(BotContext $ctx): void
    {
        $code = self::RELATIVE_CODE_BY_KEYWORD[$ctx->getKeywordText($ctx->text)] ?? null;

        if ($code === null) {
            $this->showRelative($ctx);
            return;
        }

        $this->finalizeDriver($ctx, $code);
    }

    /**
     * Common tail for both handleDriver() (owner-as-driver, no relation
     * prompt) and handleRelative() (manual driver, relation chosen via
     * keyboard): appends the passport-validated driver stashed in
     * pendingDriver to both $ctx->drivers (EAI shape) and
     * police_data['drivers'] (Gross shape, carries the raw 0-10 code).
     */
    private function finalizeDriver(BotContext $ctx, int $relativeCode): void
    {
        $pending = $ctx->pendingDriver != '' ? $ctx->pendingDriver : null;
        if (!$pending) {
            $this->policeSeasonStage->showPoliceSeason($ctx);
            return;
        }

        $drivers = $ctx->drivers != '' ? $ctx->drivers : [];
        $drivers[] = [
            'birthDate' => $pending['birthdate'],
            'number' => $pending['number'],
            'seria' => $pending['seria'],
            // showConfirmPage() reads these to list drivers by name in the
            // confirmation text — must stay alongside the EAI-shape fields above.
            'firstName' => $pending['firstName'],
            'lastName' => $pending['lastName'],
        ];
        $ctx->drivers = $drivers;

        $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
        $police_data['drivers'][] = [
            'document'      => $pending['seria'] . $pending['number'],
            'birth_date'    => substr($pending['birthdate'], 0, 10),
            'relative_type' => $relativeCode,
        ];
        $ctx->police_data = $police_data;
        $ctx->pendingDriver = '';

        $ctx->sendMessageAdmin(json_encode($police_data));

        $count = count($drivers);
        $driverName = $pending['firstName'] . ' ' . $pending['lastName'];

        if ($count < 5){
            $text = sprintf($ctx->getMText('Drivers saved'), $count, $driverName);
            $this->showDriver($ctx, true, $text);
        }else{
            $this->policeSeasonStage->showPoliceSeason($ctx);
        }
    }
}
