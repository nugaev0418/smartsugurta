<?php

use yii\db\Migration;

class m260605_190000_update_referral_system_text extends Migration
{
    public function safeUp()
    {
        $this->update('{{%text}}', [
            'uz' => '🤝 Referal tizimi',
            'ru' => '🤝 Реферальная система',
        ], ['keyword' => 'Referral system']);
    }

    public function safeDown()
    {
        $this->update('{{%text}}', [
            'uz' => 'Referal tizimi',
            'ru' => 'Реферальная система',
        ], ['keyword' => 'Referral system']);
    }
}
