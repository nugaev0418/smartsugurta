<?php

namespace backend\component\bot;

use common\models\Text;

/**
 * Text-model lookups for the bot's per-user language, extracted verbatim
 * from BotController::getMText()/getKeywordText(). Stateless — takes $lang
 * on every call rather than caching it, since BotController::changeLang()
 * mutates the current user's language mid-request and later lookups in the
 * same request must see the new value immediately.
 */
class BotTextService
{
    public function m(string $lang, string $keyword): string
    {
        $text = Text::findOne(['keyword' => $keyword]);
        if (!is_null($text)) {
            $text = $text->toArray();
            if (isset($text[$lang])) {
                return $text[$lang];
            }
        }

        return '';
    }

    public function keywordOf(string $lang, string $displayText): string
    {
        if (!empty(Text::findOne([$lang => $displayText]))) {
            $text = Text::findOne([$lang => $displayText])->toArray();
            if (isset($text['keyword'])) {
                return $text['keyword'];
            }
        }

        return '';
    }
}
