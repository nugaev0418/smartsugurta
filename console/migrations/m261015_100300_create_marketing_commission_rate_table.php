<?php

use yii\db\Migration;

/**
 * Provayder (Police::PROVIDER_*) va hudud (tashkent/other) bo'yicha
 * komissiya foizi — admin paneldan sozlanadi, kelajakda yangi provayderlar
 * qo'shilsa ham qattiq kodlanmagan jadval orqali kengaytiriladi.
 */
class m261015_100300_create_marketing_commission_rate_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%marketing_commission_rate}}', [
            'id'          => $this->primaryKey(),
            'provider_id' => $this->integer()->notNull(),
            'region'      => $this->string(10)->notNull(),
            'percent'     => $this->decimal(5, 2)->notNull(),
            'created_at'  => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at'  => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        $this->createIndex(
            '{{%idx-marketing_commission_rate-provider_id-region}}',
            '{{%marketing_commission_rate}}',
            ['provider_id', 'region'],
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%marketing_commission_rate}}');
    }
}
