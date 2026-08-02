<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%paynet}}`.
 */
class m260802_120000_create_paynet_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%paynet}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'paynet_id' => $this->integer()->notNull(),
            'api_token' => $this->string()->notNull(),
            'is_active' => $this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        $this->createIndex(
            '{{%idx-paynet-paynet_id}}',
            '{{%paynet}}',
            'paynet_id',
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex(
            '{{%idx-paynet-paynet_id}}',
            '{{%paynet}}'
        );

        $this->dropTable('{{%paynet}}');
    }
}
