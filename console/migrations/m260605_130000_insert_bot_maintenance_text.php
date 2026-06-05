<?php

use yii\db\Migration;

class m260605_130000_insert_bot_maintenance_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'bot_maintenance',
            'uz'      => "⚙️ Hozirgi vaqtda botda texnik ishlar olib borilmoqda, iltimos birozdan keyin urinib ko'ring.",
            'ru'      => "⚙️ В настоящее время в боте ведутся технические работы, пожалуйста, попробуйте позже.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'bot_maintenance']);
    }
}
