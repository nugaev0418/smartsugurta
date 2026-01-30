<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%text}}`.
 */
class m260112_121717_create_text_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%text}}', [
            'id' => $this->primaryKey(),
            'keyword' => $this->string(),
            'uz' => $this->text(),
            'ru' => $this->text(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%text}}');
    }
}
