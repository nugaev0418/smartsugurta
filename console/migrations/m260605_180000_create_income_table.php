<?php

use yii\db\Migration;

class m260605_180000_create_income_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%income}}', [
            'id'         => $this->primaryKey(),
            'user_id'    => $this->integer()->notNull(),
            'amount'     => $this->integer()->notNull()->defaultValue(0),
            'reason'     => $this->string()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey('fk_income_user', '{{%income}}', 'user_id', '{{%botuser}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_income_user', '{{%income}}');
        $this->dropTable('{{%income}}');
    }
}
