<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%relative}}`.
 */
class m260112_105632_create_relative_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%relative}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'relativeId' =>$this->string(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%relative}}');
    }
}
