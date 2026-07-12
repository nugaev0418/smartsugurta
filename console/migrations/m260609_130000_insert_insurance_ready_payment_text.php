<?php

use yii\db\Migration;

class m260609_130000_insert_insurance_ready_payment_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'insurance_ready_payment',
            'uz'      => "ID: %s <b>Sug'urtangiz tayyor! Pastdagi havola orqali to'lovni amalga oshiring.\n❗Agar Click orqali to'lov amalga oshmasa, Payme orqali to'lov qilishingiz mumkin.</b> \n<a href='%s'>👉 Payme</a>\n<a href='%s'>👉 Click</a>",
            'ru'      => "ID: %s <b>Ваша страховка готова! Перейдите по ссылке ниже, чтобы произвести оплату.\n❗Если оплата через Click не проходит, вы можете оплатить через Payme.</b> \n<a href='%s'>👉 Payme</a>\n<a href='%s'>👉 Click</a>",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'insurance_ready_payment']);
    }
}
