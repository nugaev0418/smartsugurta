<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%botuser}}`.
 */
class m260109_110919_create_botuser_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%botuser}}', [
            'id' => $this->primaryKey(),
            'chat_id' => $this->bigInteger()->notNull(),
            'balance' => $this->integer()->defaultValue(0),
            'fname' => $this->string(),
            'lname' => $this->string(),
            'username' => $this->string(),
            'phone' => $this->string(),
            'status' => $this->integer()->defaultValue(1),
            'data' => $this->json(),
            'is_admin' => $this->integer()->defaultValue(0),
            'is_banned' => $this->integer()->defaultValue(0),
            'step' => $this->string(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%botuser}}');
    }
}
