<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\MarketingAttributionService;
use backend\models\Pages;

/**
 * Pages::SURVEY_SOURCE — deeplinksiz (sof /start) kelgan yangi
 * foydalanuvchiga bir martalik "qayerdan bildingiz" so'rovnomasi.
 * LangStageHandler::handle() orqali til tanlangandan keyin ko'rsatiladi
 * (faqat $ctx->needsSurvey true bo'lganda — BotController::actionStart()da
 * belgilanadi). Global buyruqlar (BotCommandRouter) joriy sahifadan qat'i
 * nazar ishlagani uchun, foydalanuvchi variantlardan birini tanlamay boshqa
 * tugma bossa — survey qayta ko'rsatilmaydi, shunchaki o'tkazib yuboriladi.
 */
class SurveyStageHandler implements BotStageInterface
{
    // Button keyword (Text.keyword) -> marketing_attribution.survey_source kaliti.
    private const SURVEY_KEY_BY_KEYWORD = [
        'From Instagram blogger' => 'instagram_blogger',
        'From Instagram ads' => 'instagram_ads',
        'From Telegram channel' => 'telegram_channel',
        'Friend recommended' => 'friend_referral',
        'From Google or other' => 'google_other',
        'Other, do not remember' => 'other_unknown',
    ];

    private MarketingAttributionService $attribution;

    public function __construct(
        private MainMenuStageHandler $mainMenu,
        ?MarketingAttributionService $attribution = null
    ) {
        $this->attribution = $attribution ?? new MarketingAttributionService();
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $ctx->page = Pages::SURVEY_SOURCE;

        $text = $ctx->getMText('How did you find us?');
        $option = [];
        foreach (array_keys(self::SURVEY_KEY_BY_KEYWORD) as $keyword) {
            $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText($keyword))];
        }

        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handle(BotContext $ctx): void
    {
        $surveyKey = self::SURVEY_KEY_BY_KEYWORD[$ctx->getKeywordText($ctx->text)] ?? null;

        if ($surveyKey === null) {
            // Javob kutilgan variantlardan biri emas (masalan global tugma
            // bosildi) — bezovta qilmaslik uchun qayta so'ralmaydi,
            // shunchaki asosiy menyuga o'tkaziladi.
            $this->mainMenu->show($ctx);
            return;
        }

        $this->attribution->recordSurveyAnswer($ctx->chat_id, $surveyKey);
        $this->mainMenu->show($ctx);
    }
}
