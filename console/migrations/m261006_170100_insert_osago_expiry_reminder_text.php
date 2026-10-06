<?php

use yii\db\Migration;

class m261006_170100_insert_osago_expiry_reminder_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'osago_expiry_reminder',
            'uz' => "🚗 <b>%s</b> raqamli transportingizning sug'urta muddati <b>%d kun</b> qoldi. Botda osongina sug'urta qilib oling! Oldindan muddatini belgilab, sug'urtani oldindan ham rasmiylashtirishingiz mumkin.",
            'ru' => "🚗 Срок действия ОСАГО для вашего транспортного средства с номером <b>%s</b> истекает через <b>%d дней</b>. Оформите страховку легко через бота! Вы можете заранее указать дату и оформить страховку заблаговременно.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'osago_expiry_reminder']);
    }
}
