<?php

use yii\db\Migration;

class m260605_160000_insert_referral_system_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'Referral system',
            'uz'      => "Referal tizimi",
            'ru'      => "Реферальная система",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'Referral system']);
    }
}
