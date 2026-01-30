<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%owner}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%police}}`
 */
class m260112_123539_create_owner_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%owner}}', [
            'id' => $this->primaryKey(),
            'inn' => $this->bigInteger(),
            'name' => $this->string(),
            'address' => $this->string(),
            'type' => $this->string(),
            'firstName' => $this->string(),
            'middlename' => $this->string(),
            'lastname' => $this->string(),
            'pinfl' => $this->bigInteger(),
            'districtId' => $this->string(),
            'police_id' => $this->integer(),
        ]);

        // creates index for column `police_id`
        $this->createIndex(
            '{{%idx-owner-police_id}}',
            '{{%owner}}',
            'police_id'
        );

        // add foreign key for table `{{%police}}`
        $this->addForeignKey(
            '{{%fk-owner-police_id}}',
            '{{%owner}}',
            'police_id',
            '{{%police}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%police}}`
        $this->dropForeignKey(
            '{{%fk-owner-police_id}}',
            '{{%owner}}'
        );

        // drops index for column `police_id`
        $this->dropIndex(
            '{{%idx-owner-police_id}}',
            '{{%owner}}'
        );

        $this->dropTable('{{%owner}}');
    }
}
