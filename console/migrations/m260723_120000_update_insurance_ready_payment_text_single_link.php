<?php

use yii\db\Migration;

class m260723_120000_update_insurance_ready_payment_text_single_link extends Migration
{
    public function safeUp()
    {
        $this->update('{{%text}}', [
            'uz' => "ID: %s <b>Sug'urtangiz tayyor! Quyidagi havola orqali %s orqali to'lovni amalga oshiring.</b> \n<a href='%s'>👉 To'lov</a>",
            'ru' => "ID: %s <b>Ваша страховка готова! Перейдите по ссылке ниже, чтобы оплатить через %s.</b> \n<a href='%s'>👉 Оплата</a>",
        ], ['keyword' => 'insurance_ready_payment']);
    }

    public function safeDown()
    {
        $this->update('{{%text}}', [
            'uz' => "ID: %s <b>Sug'urtangiz tayyor! Pastdagi havola orqali to'lovni amalga oshiring.\n❗Agar Click orqali to'lov amalga oshmasa, Payme orqali to'lov qilishingiz mumkin.</b> \n<a href='%s'>👉 Payme</a>\n<a href='%s'>👉 Click</a>",
            'ru' => "ID: %s <b>Ваша страховка готова! Перейдите по ссылке ниже, чтобы произвести оплату.\n❗Если оплата через Click не проходит, вы можете оплатить через Payme.</b> \n<a href='%s'>👉 Payme</a>\n<a href='%s'>👉 Click</a>",
        ], ['keyword' => 'insurance_ready_payment']);
    }
}
