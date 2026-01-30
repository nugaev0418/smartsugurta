<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%payment}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%botuser}}`
 */
class m260126_125025_create_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payment}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'type' => $this->integer(),
            'account' => $this->string(),
            'amount' => $this->integer(),
            'status' => $this->integer()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'payment_id' => $this->string(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        // creates index for column `user_id`
        $this->createIndex(
            '{{%idx-payment-user_id}}',
            '{{%payment}}',
            'user_id'
        );

        // add foreign key for table `{{%botuser}}`
        $this->addForeignKey(
            '{{%fk-payment-user_id}}',
            '{{%payment}}',
            'user_id',
            '{{%botuser}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%botuser}}`
        $this->dropForeignKey(
            '{{%fk-payment-user_id}}',
            '{{%payment}}'
        );

        // drops index for column `user_id`
        $this->dropIndex(
            '{{%idx-payment-user_id}}',
            '{{%payment}}'
        );

        $this->dropTable('{{%payment}}');
    }
}
