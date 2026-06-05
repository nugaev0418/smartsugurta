<?php

use yii\db\Migration;

class m260605_140000_insert_maintenance_texts extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'police_maintenance',
            'uz'      => "⚙️ Hozirda sug'urta yaratish qismida texnik ishlar qilinmoqda, iltimos keyinroq urinib ko'ring.",
            'ru'      => "⚙️ В данный момент в разделе создания страховки ведутся технические работы, пожалуйста, попробуйте позже.",
        ]);

        $this->insert('{{%text}}', [
            'keyword' => 'payment_maintenance',
            'uz'      => "⚙️ Hozirda to'lov tizimida texnik ishlar qilinmoqda, iltimos keyinroq urinib ko'ring.",
            'ru'      => "⚙️ В данный момент в платёжной системе ведутся технические работы, пожалуйста, попробуйте позже.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'police_maintenance']);
        $this->delete('{{%text}}', ['keyword' => 'payment_maintenance']);
    }
}
