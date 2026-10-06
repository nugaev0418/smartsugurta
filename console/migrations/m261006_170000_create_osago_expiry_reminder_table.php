<?php

use yii\db\Migration;

/**
 * "Mening avtolarim"dagi saqlangan avtomobillarning (keshlangan) OSAGO
 * polisasi muddati tugashiga 7/3/1 kun qolganda yuboriladigan eslatmalarni
 * kuzatib boradi — bitta (avtomobil, polisa tugash sanasi, milestone)
 * kombinatsiyasi uchun eslatma faqat bir marta yuborilishini UNIQUE
 * indeks orqali kafolatlaydi.
 */
class m261006_170000_create_osago_expiry_reminder_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%osago_expiry_reminder}}', [
            'id' => $this->primaryKey(),
            'saved_vehicle_id' => $this->integer()->notNull(),
            'policy_end_date' => $this->date()->notNull(),
            'milestone_days' => $this->smallInteger()->notNull(),
            'sent_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey(
            'fk_oer_saved_vehicle',
            '{{%osago_expiry_reminder}}',
            'saved_vehicle_id',
            '{{%saved_vehicle}}',
            'id',
            'CASCADE'
        );

        $this->createIndex(
            '{{%uq-oer-vehicle-date-milestone}}',
            '{{%osago_expiry_reminder}}',
            ['saved_vehicle_id', 'policy_end_date', 'milestone_days'],
            true
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_oer_saved_vehicle', '{{%osago_expiry_reminder}}');
        $this->dropTable('{{%osago_expiry_reminder}}');
    }
}
