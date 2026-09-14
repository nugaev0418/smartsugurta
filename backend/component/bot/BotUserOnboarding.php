<?php

namespace backend\component\bot;

use common\models\Botuser;
use common\models\History;

/**
 * Extracted verbatim from BotController::isUser()/addUser()/setHistory():
 * first-contact bookkeeping for a Telegram chat — logging the incoming
 * message to History and creating the Botuser row on first contact.
 */
class BotUserOnboarding
{
    public function isUser(string $chatId): bool
    {
        return Botuser::find()->where(['chat_id' => $chatId])->one() !== null;
    }

    public function addUser(string $chatId, $firstname, $lastname, $username): void
    {
        $model = new Botuser();
        $model->chat_id       = $chatId;
        $model->fname         = $firstname;
        $model->lname         = $lastname;
        $model->username      = $username;
        $model->referral_code = Botuser::generateReferralCode();
        $model->save();
    }

    public function setHistory(string $chatId, $message): void
    {
        $model = new History();
        $model->chat_id = $chatId;
        $model->message = $message;
        $model->save();
    }

    /**
     * Records the interaction and creates the Botuser row on first contact,
     * in the same order actionStart() always did (history first, then the
     * isUser/addUser check). Returns true when a new Botuser row was just
     * created.
     */
    public function ensureUser(string $chatId, $text, $firstname, $lastname, $username): bool
    {
        if (is_numeric($chatId)) {
            $this->setHistory($chatId, $text);
        }

        $isNewUser = !$this->isUser($chatId);
        if ($isNewUser) {
            $this->addUser($chatId, $firstname, $lastname, $username);
        }

        return $isNewUser;
    }
}
