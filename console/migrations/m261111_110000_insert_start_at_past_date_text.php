<?php

use yii\db\Migration;

class m261111_110000_insert_start_at_past_date_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'start at past date',
            'uz' => "❌ Boshlanish sanasi bugundan oldin bo'lishi mumkin emas. Bugungi yoki keyingi sanani kiriting.",
            'ru' => "❌ Дата начала не может быть раньше сегодняшней. Введите сегодняшнюю или более позднюю дату.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'start at past date']);
    }
}
