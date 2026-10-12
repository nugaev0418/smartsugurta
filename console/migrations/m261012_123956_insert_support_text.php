<?php

use yii\db\Migration;

class m261012_123956_insert_support_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'Support',
            'uz'      => "☎️ Qo'llab-quvvatlash",
            'ru'      => "☎️ Поддержка",
        ]);
        $this->insert('{{%text}}', [
            'keyword' => 'support page message',
            'uz'      => "Savollaringiz bo'lsa, tez orada siz bilan bog'lanamiz.",
            'ru'      => "Если у вас есть вопросы, мы скоро с вами свяжемся.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'Support']);
        $this->delete('{{%text}}', ['keyword' => 'support page message']);
    }
}
