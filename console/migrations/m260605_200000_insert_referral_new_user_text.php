<?php

use yii\db\Migration;

class m260605_200000_insert_referral_new_user_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'referral_new_user',
            'uz'      => "🎉 Sizning referal havolangiz orqali yangi foydalanuvchi qo'shildi!",
            'ru'      => "🎉 По вашей реферальной ссылке зарегистрировался новый пользователь!",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'referral_new_user']);
    }
}
