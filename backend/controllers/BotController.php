<?php

namespace backend\controllers;

use backend\component\bot\BotCommandRouter;
use backend\component\bot\BotContext;
use backend\component\bot\BotMessenger;
use backend\component\bot\BotStageRegistry;
use backend\component\bot\BotTextService;
use backend\component\bot\BotUserOnboarding;
use backend\component\bot\BotUserState;
use backend\component\bot\stage\AdminStageHandler;
use backend\component\bot\stage\ConfirmStageHandler;
use backend\component\bot\stage\DriverRestrictionStageHandler;
use backend\component\bot\stage\DriverStageHandler;
use backend\component\bot\stage\LangStageHandler;
use backend\component\bot\stage\MainMenuStageHandler;
use backend\component\bot\stage\OwnerStageHandler;
use backend\component\bot\stage\PhoneStageHandler;
use backend\component\bot\stage\PoliceSeasonStageHandler;
use backend\component\bot\stage\VehicleLookupStageHandler;
use backend\component\bot\stage\WalletStageHandler;
use backend\component\bot\WebhookGuard;
use backend\component\DeeplinkService;
use backend\component\ReferralService;
use backend\models\Pages;
use common\models\Setting;
use common\models\Text;
use Yii;
use yii\base\ErrorException;
use yii\helpers\Url;
use yii\web\Controller;

class BotController extends Controller
{

    public $enableCsrfValidation = false;
    public $chat_id;
    public $text;
    public $data;
    public $telegram;
    public $firstname;
    public $lastname;
    public $username;

    protected ?BotMessenger $messenger = null;
    protected ?BotTextService $textService = null;
    protected ?BotUserState $state = null;
    protected ?WebhookGuard $webhookGuard = null;
    protected ?BotUserOnboarding $onboarding = null;

    const PAYMENT_CHANNEL       = '-1003501874314',
         PAYMENT_CHANNEL_ADMIN  = '-1001571664655',
          ADMIN_ID              = '3673579',
          ORDER_CHANNEL         = '-1003782162980',
          BOT_USERNAME          = 'smartsugurtabot';


    public function actionStart()
    {
        $this->telegram = Yii::$app->telegram;
        $this->messenger = new BotMessenger($this->telegram, self::ADMIN_ID);
        $this->textService = new BotTextService();
        $this->webhookGuard = new WebhookGuard();
        $this->onboarding = new BotUserOnboarding();

        $this->data = $this->telegram->getData();
        $this->text = $this->telegram->Text();
        $this->firstname = $this->telegram->FirstName();
        $this->lastname = $this->telegram->LastName();
        $this->username = $this->telegram->Username();
        $this->chat_id = isset($message['chat']['id']) ? $message['chat']['id'] : '';
        $this->chat_id = $this->telegram->ChatID();
        $this->state = new BotUserState($this->chat_id);

        $updateId = $this->telegram->UpdateID();
        if ($this->webhookGuard->isDuplicateUpdate($updateId)) {
            // Telegram shu update'ni qayta yubordi (webhook javobi kechikkani uchun) — qayta ishlanmaydi
            return 'ok';
        }

        $lockKey = $this->webhookGuard->tryLock($this->chat_id);

        if (is_numeric($this->chat_id) && !$lockKey) {
            try {
                $this->messenger->send($this->chat_id, "So'rovingiz hali ishlanmoqda, iltimos biroz kuting...");
            } catch (ErrorException $e) {
                Yii::error($e->getMessage());
            }
            return 'ok';
        }

        try {
            $isNewUser = $this->onboarding->ensureUser($this->chat_id, $this->text, $this->firstname, $this->lastname, $this->username);

            if (!Setting::getBotStatus()) {
                $lang = $this->lang ?: 'uz';
                $record = Text::findOne(['keyword' => 'bot_maintenance']);
                $msg = ($record && $record->$lang)
                    ? $record->$lang
                    : "Hozirgi vaqtda botda texnik ishlar olib borilmoqda, iltimos birozdan keyin urinib ko'ring.";
                $this->messenger->send($this->chat_id, $msg);
                return 'ok';
            }

            $referralNotify = $this->text
                ? (new ReferralService())->processStartCommand($this->text, $this->chat_id, $isNewUser)
                : null;
            if ($referralNotify) {
                $this->messenger->sendWithId($referralNotify['chat_id'], $referralNotify['message']);
            }
            if ($this->text && preg_match('/^\/start ref_/', $this->text)) {
                $this->text = '/start';
            }

            if ($this->text && (new DeeplinkService())->processStartCommand($this->text, $this->chat_id, $isNewUser)) {
                $this->text = '/start';
            }

            $ctx = new BotContext(
                $this->chat_id,
                $this->text,
                $this->data,
                $this->telegram,
                $this->state,
                $this->messenger,
                $this->textService,
                self::ADMIN_ID
            );

            if (!$this->buildCommandRouter()->route($ctx)) {
                $this->buildStageRegistry()->get($ctx->page)?->handle($ctx);
            }
        }catch (ErrorException $e){
            Yii::error($e->getMessage());
            $this->response->statusCode = 200;
        } finally {
            if ($lockKey) {
                $this->webhookGuard->unlock($lockKey);
            }
        }
        return 'ok';
    }

    /**
     * Builds the Pages:: -> stage-handler map used for actionStart()'s final
     * dispatch (what used to be the inner switch($this->page)). Adding a new
     * FSM stage: add a Pages:: constant, a handler class implementing
     * BotStageInterface, and one register() call here.
     */
    private function buildStageRegistry(): BotStageRegistry
    {
        $registry = new BotStageRegistry();

        $registry->register(Pages::LANG, Yii::createObject(LangStageHandler::class));
        $registry->register(Pages::MAIN, Yii::createObject(MainMenuStageHandler::class));
        $registry->register(Pages::PHONE, Yii::createObject(PhoneStageHandler::class));

        $vehicleLookup = Yii::createObject(VehicleLookupStageHandler::class);
        $registry->register(Pages::LISENCE_NUMBER, $vehicleLookup);
        $registry->register(Pages::TEXPASS_SERIA, $vehicleLookup);
        $registry->register(Pages::TEXPASS_NUMBER, $vehicleLookup);

        $registry->register(Pages::OWNER_PASS, Yii::createObject(OwnerStageHandler::class));

        $driverRestriction = Yii::createObject(DriverRestrictionStageHandler::class);
        $registry->register(Pages::DRIVER_RESTRICTION_TYPE, $driverRestriction);
        $registry->register(Pages::OWNER_IS_DRIVER, $driverRestriction);

        $driverStage = Yii::createObject(DriverStageHandler::class);
        $registry->register(Pages::DRIVER_PAGE, $driverStage);
        $registry->register(Pages::RELATIVE_PAGE, $driverStage);

        $policeSeason = Yii::createObject(PoliceSeasonStageHandler::class);
        $registry->register(Pages::POLICE_SEASON_TYPE, $policeSeason);
        $registry->register(Pages::START_AT, $policeSeason);
        $registry->register(Pages::PAYMENT_TYPE, $policeSeason);

        $registry->register(Pages::CONFIRM_PAGE, Yii::createObject(ConfirmStageHandler::class));

        $wallet = Yii::createObject(WalletStageHandler::class);
        $registry->register(Pages::WALLET_PAGE, $wallet);
        $registry->register(Pages::WITHDRAW_TYPE_PAGE, $wallet);
        $registry->register(Pages::WITHDRAW_ACCOUNT_PAGE, $wallet);
        $registry->register(Pages::WITHDRAW_AMOUNT_PAGE, $wallet);

        $admin = Yii::createObject(AdminStageHandler::class);
        $registry->register(Pages::ADMIN_PAGE, $admin);
        $registry->register(Pages::BROADCAST_PAGE, $admin);

        return $registry;
    }

    private function buildCommandRouter(): BotCommandRouter
    {
        return Yii::createObject(BotCommandRouter::class);
    }

    public function __get($name)
    {
        return $this->state->get($name);
    }

    public function __set($name, $value)
    {
        $this->state->set($name, $value);
    }

    public static function toIsoDate($date)
    {
        $dt = \DateTime::createFromFormat('d.m.Y', $date, new \DateTimeZone('UTC'));
        return $dt ? $dt->format('Y-m-d\TH:i:s.v\Z') : null;
    }

    public function setWebhook(){
        $url = Url::base('https') .'/'. Yii::$app->controller->id .'/'. $this->action->id;
        return Yii::$app->telegram->setWebhook($url);
    }
}
