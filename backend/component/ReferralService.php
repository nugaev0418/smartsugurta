<?php

namespace backend\component;

use common\models\Botuser;
use common\models\Income;
use common\models\Police;
use common\models\Setting;
use common\models\Text;

class ReferralService
{
    public function __construct(private string $botUsername = 'smartsugurtabot')
    {
    }

    public function processStartCommand(string $text, string $chatId, bool $isNewUser): ?array
    {
        if (!preg_match('/^\/start ref_(\w+)$/', $text, $m)) {
            return null;
        }

        $referrer = Botuser::find()->where(['referral_code' => $m[1]])->one();
        $self     = Botuser::find()->where(['chat_id' => $chatId])->one();

        if (!$referrer || !$self || $self->referred_by || $referrer->id === $self->id) {
            return null;
        }

        $self->referred_by = $referrer->id;
        $self->save(false);

        $refLang = 'uz';
        if ($referrer->data) {
            $refData = json_decode($referrer->data, true);
            $refLang = $refData['lang'] ?? 'uz';
        }

        $record = Text::findOne(['keyword' => 'referral_new_user']);
        $message = ($record && $record->$refLang)
            ? $record->$refLang
            : ($refLang === 'ru'
                ? "🎉 По вашей реферальной ссылке зарегистрировался новый пользователь!"
                : "🎉 Sizning referal havolangiz orqali yangi foydalanuvchi qo'shildi!");

        return [
            'chat_id' => $referrer->chat_id,
            'message' => $message,
        ];
    }

    public function buildPageData(string $chatId): array
    {
        $user = Botuser::find()->where(['chat_id' => $chatId])->one();

        $referralLink = "https://t.me/{$this->botUsername}?start=ref_{$user->referral_code}";

        $referralIds   = Botuser::find()->select('id')->where(['referred_by' => $user->id])->column();
        $referralCount = count($referralIds);

        $insuranceCount = 0;
        $totalEarned    = 0;
        if ($referralCount > 0) {
            $insuranceCount = (int)Police::find()
                ->where(['user_id' => $referralIds, 'payment_status' => 1])
                ->count();

            $totalEarned = (int)(Income::find()
                ->where(['user_id' => $user->id])
                ->sum('amount') ?? 0);
        }

        $bonusPercent    = Setting::getReferralPercent();
        $earnedFormatted = number_format($totalEarned, 0, '.', ' ');

        $shareMessage = urlencode(
            "\n🎁 Bu bot orqali avtosug'urta rasmiylashtirsangiz $bonusPercent% bonus olasiz!\n\n"
            . "Bonusni plastik karta yoki telefon raqamga chiqarib olsa bo'ladi.\n\n"
        );
        $shareUrl = "https://t.me/share/url?url=" . urlencode($referralLink) . "&text=$shareMessage";

        $text = "🤝 <b>Referal tizimi</b>\n\n"
              . "🔗 Sizning referal havolangiz:\n$referralLink\n\n"
              . "👥 Referallar soni: <b>$referralCount</b>\n"
              . "📋 Ularning sug'urtalari: <b>$insuranceCount</b>\n"
              . "💰 Jami ishlagan: <b>$earnedFormatted so'm</b>\n\n"
              . "📊 <b>Bonus jadvali:</b>\n"
              . "Har bir to'langan sug'urta uchun: <b>$bonusPercent% bonus</b>\n\n"
              . "ℹ️ Bonus hamyon balansiga avtomatik qo'shiladi.";

        return [
            'text'       => $text,
            'shareUrl'   => $shareUrl,
            'shareLabel' => "📤 Do'stlarga ulashish",
        ];
    }
}
