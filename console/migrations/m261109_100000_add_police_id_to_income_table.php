<?php

use yii\db\Migration;

/**
 * Bitta polis uchun bir turdagi bonus faqat bir marta yozilishini baza darajasida
 * kafolatlaydi. Eski yozuvlarda police_id NULL qoladi: MySQL unique indeksi NULL'larni
 * takror hisoblamaydi, tarixdagi ikki marta berilgan bonuslar esa migratsiyani buzmaydi.
 */
class m261109_100000_add_police_id_to_income_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%income}}', 'police_id', $this->integer()->null());
        $this->createIndex('ux_income_police_type', '{{%income}}', ['police_id', 'type'], true);
    }

    public function safeDown()
    {
        $this->dropIndex('ux_income_police_type', '{{%income}}');
        $this->dropColumn('{{%income}}', 'police_id');
    }
}
