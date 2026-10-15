<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\models\Pages;

/**
 * Pages::LANG — extracted verbatim from
 * BotController::showLangPage()/handleLangPage().
 */
class LangStageHandler implements BotStageInterface
{
    public function __construct(
        private MainMenuStageHandler $mainMenu,
        private SurveyStageHandler $surveyStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $ctx->page = Pages::LANG;

        $text = "Iltimos tilni tanlagan.\nПожалуйста выберите язык.";
        $option = [
            [
                $ctx->telegram->buildKeyboardButton("O'zbekcha"),
                $ctx->telegram->buildKeyboardButton("Русский"),
            ],
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->text) {
            case "O'zbekcha":
                $ctx->lang = 'uz';
                $this->afterLangChosen($ctx);
                break;
            case "Русский":
                $ctx->lang = 'ru';
                $this->afterLangChosen($ctx);
                break;
            default:
                $this->show($ctx);
        }
    }

    private function afterLangChosen(BotContext $ctx): void
    {
        if ($ctx->needsSurvey) {
            $ctx->needsSurvey = '';
            $this->surveyStage->show($ctx);
            return;
        }

        $this->mainMenu->show($ctx);
    }
}
