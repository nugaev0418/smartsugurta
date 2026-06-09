<?php

use yii\db\Migration;

class m260609_110000_create_broadcast_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%broadcast_log}}', [
            'id'                  => $this->primaryKey(),
            'broadcast_id'        => $this->integer()->notNull(),
            'user_id'             => $this->integer()->notNull(),
            'chat_id'             => $this->bigInteger()->notNull(),
            'telegram_message_id' => $this->integer()->null(),
            'created_at'          => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey('fk_bl_broadcast', '{{%broadcast_log}}', 'broadcast_id', '{{%broadcast}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_bl_user',      '{{%broadcast_log}}', 'user_id',      '{{%botuser}}',   'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_bl_broadcast', '{{%broadcast_log}}');
        $this->dropForeignKey('fk_bl_user',      '{{%broadcast_log}}');
        $this->dropTable('{{%broadcast_log}}');
    }
}
