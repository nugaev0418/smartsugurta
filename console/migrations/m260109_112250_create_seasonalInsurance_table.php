<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%season}}`.
 */
class m260109_112250_create_seasonalInsurance_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%season}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'days' => $this->integer(),
            'seasonId' => $this->string(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%season}}');
    }
}
