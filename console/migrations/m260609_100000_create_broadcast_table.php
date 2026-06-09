<?php

use yii\db\Migration;

class m260609_100000_create_broadcast_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%broadcast}}', [
            'id'           => $this->primaryKey(),
            'message_type' => $this->string(50)->notNull(),
            'message_data' => $this->json()->notNull(),
            'from_chat_id' => $this->bigInteger()->notNull(),
            'total_users'  => $this->integer()->notNull()->defaultValue(0),
            'sent_count'   => $this->integer()->notNull()->defaultValue(0),
            'status'       => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at'   => $this->timestamp()->defaultExpression('NOW()'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%broadcast}}');
    }
}
