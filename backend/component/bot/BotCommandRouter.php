<?php

namespace backend\component\bot;

use backend\component\bot\stage\AdminStageHandler;
use backend\component\bot\stage\LangStageHandler;
use backend\component\bot\stage\MainMenuStageHandler;
use backend\component\bot\stage\MyVehiclesStageHandler;
use backend\component\bot\stage\PhoneStageHandler;
use backend\component\bot\stage\WalletStageHandler;
use common\models\Setting;
use common\models\Text;

/**
 * BotController::actionStart()'s old outer switch($this->text): global
 * commands that work regardless of the user's current FSM page (Cancel/
 * Main menu/Wallet/Support/Referral/Prices/Language/admin buttons), plus
 * the OSAGO/Wallet maintenance gates. route() returns true when the text
 * matched one of these commands — the caller must then skip the page-based
 * dispatch entirely, exactly like the original switch's implicit
 * fallthrough-to-default boundary.
 */
class BotCommandRouter
{
    public function __construct(
        private LangStageHandler $langStage,
        private MainMenuStageHandler $mainMenu,
        private PhoneStageHandler $phoneStage,
        private WalletStageHandler $walletStage,
        private AdminStageHandler $adminStage,
        private MyVehiclesStageHandler $myVehiclesStage
    ) {
    }

    public function route(BotContext $ctx): bool
    {
        switch ($ctx->text) {
            case '/start':
                if ($ctx->lang) {
                    $this->mainMenu->show($ctx);
                } else {
                    $this->langStage->show($ctx);
                }
                return true;
            case $ctx->getMText('Cancel'):
                $this->mainMenu->show($ctx);
                return true;
            case $ctx->getMText('Main menu'):
                $this->mainMenu->show($ctx);
                return true;
            case $ctx->getMText('Language selection'):
                $this->changeLang($ctx);
                return true;
            case $ctx->getMText("BEGIN OSAGO BUTTON"):
                if (!Setting::getPoliceStatus()) {
                    $lang   = $ctx->lang ?: 'uz';
                    $record = Text::findOne(['keyword' => 'police_maintenance']);
                    $msg    = ($record && $record->$lang)
                        ? $record->$lang
                        : "Hozirda sug'urta yaratish qismida texnik ishlar qilinmoqda, iltimos keyinroq urinib ko'ring.";
                    $ctx->sendMessage($msg);
                    return true;
                }
                $this->clearDatas($ctx);
                $this->phoneStage->show($ctx);
                return true;
            case $ctx->getMText("Wallet"):
                if ($this->walletStage->isPaymentMaintenance($ctx)) {
                    return true;
                }
                $this->walletStage->showWallet($ctx);
                return true;
            case $ctx->getMText("Support"):
                $this->mainMenu->showSupport($ctx);
                return true;
            case $ctx->getMText("Referral system"):
                $this->mainMenu->showReferral($ctx);
                return true;
            case $ctx->getMText("Prices"):
                $this->mainMenu->showPrices($ctx);
                return true;
            case "⚙️ Admin panel":
                if ($ctx->isAdmin()) $this->adminStage->showAdmin($ctx);
                return true;
            case "📢 Xabar yuborish":
                if ($ctx->isAdmin()) $this->adminStage->showBroadcastWait($ctx);
                return true;
            case $ctx->getMText('My vehicles menu button'):
                if ($ctx->isAdmin()) $this->myVehiclesStage->show($ctx);
                return true;
            default:
                return false;
        }
    }

    private function changeLang(BotContext $ctx): void
    {
        switch ($ctx->lang){
            case 'uz': $ctx->lang = 'ru'; break;
            case 'ru': $ctx->lang = 'uz'; break;
        }
        $this->mainMenu->show($ctx);
    }

    private function clearDatas(BotContext $ctx): void
    {
        $ctx->drivers = '';
        $ctx->pendingDriver = '';
        $ctx->phone = '';
        $ctx->ownerData = '';
        $ctx->lisenceNumber = '';
        $ctx->texPassSeria = '';
        $ctx->texPassNumber = '';
        $ctx->vehicleData = '';
        $ctx->driverRestriction = '';
        $ctx->policeSeason = '';
        $ctx->startAt = '';
        $ctx->paymentType = '';
    }
}
