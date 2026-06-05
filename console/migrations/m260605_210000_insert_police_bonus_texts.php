<?php

use yii\db\Migration;

class m260605_210000_insert_police_bonus_texts extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'insurance_ready',
            'uz'      => "<b>✅ Sug'urtangiz tayyor bo'ldi!\n\n@smartsugurtabot</b>",
            'ru'      => "<b>✅ Ваша страховка готова!\n\n@smartsugurtabot</b>",
        ]);

        $this->insert('{{%text}}', [
            'keyword' => 'user_bonus_message',
            'uz'      => "🎉 Tabriklayman!\nSizning hisobingizga <b>%s</b> so'm bonus qo'shildi!\n\nChiqarib olish uchun <b>🏧 Hamyon</b> tugmasini bosing.",
            'ru'      => "🎉 Поздравляем!\nНа ваш счет зачислен бонус в размере <b>%s</b> сумов!\n\nНажмите <b>🏧 Кошелек</b> для вывода средств.",
        ]);

        $this->insert('{{%text}}', [
            'keyword' => 'referral_bonus_message',
            'uz'      => "💰 Referalingiz <b>%s</b> sug'urta rasmiyllashtirdi!\nSizga bonus qo'shildi: <b>%s</b> so'm.\n\nChiqarib olish uchun <b>🏧 Hamyon</b> tugmasini bosing.",
            'ru'      => "💰 Ваш реферал <b>%s</b> оформил страховку!\nВам начислен бонус: <b>%s</b> сумов.\n\nНажмите <b>🏧 Кошелек</b> для вывода.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'insurance_ready']);
        $this->delete('{{%text}}', ['keyword' => 'user_bonus_message']);
        $this->delete('{{%text}}', ['keyword' => 'referral_bonus_message']);
    }
}
