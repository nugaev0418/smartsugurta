<?php

use yii\db\Migration;

class m261111_120000_insert_start_at_far_date_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'start at far date',
            'uz' => "❌ Boshlanish sanasi bugundan ko'pi bilan 1 yil keyingi muddat bo'lishi mumkin. Iltimos, yaqinroq sanani kiriting.",
            'ru' => "❌ Дата начала может быть не позднее чем через 1 год от сегодняшнего дня. Введите более раннюю дату.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'start at far date']);
    }
}
