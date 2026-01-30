<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%driver}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%relative}}`
 * - `{{%police}}`
 */
class m260112_110315_create_driver_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%driver}}', [
            'id' => $this->primaryKey(),
            'passportBirthdate' => $this->date(),
            'passportNumber' => $this->string(),
            'passportSeria' => $this->string(),
            'relativeId' => $this->integer(),
            'police_id' => $this->integer(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        // creates index for column `relativeId`
        $this->createIndex(
            '{{%idx-driver-relativeId}}',
            '{{%driver}}',
            'relativeId'
        );

        // add foreign key for table `{{%relative}}`
        $this->addForeignKey(
            '{{%fk-driver-relativeId}}',
            '{{%driver}}',
            'relativeId',
            '{{%relative}}',
            'id',
            'CASCADE'
        );

        // creates index for column `police_id`
        $this->createIndex(
            '{{%idx-driver-police_id}}',
            '{{%driver}}',
            'police_id'
        );

        // add foreign key for table `{{%police}}`
        $this->addForeignKey(
            '{{%fk-driver-police_id}}',
            '{{%driver}}',
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
        // drops foreign key for table `{{%relative}}`
        $this->dropForeignKey(
            '{{%fk-driver-relativeId}}',
            '{{%driver}}'
        );

        // drops index for column `relativeId`
        $this->dropIndex(
            '{{%idx-driver-relativeId}}',
            '{{%driver}}'
        );

        // drops foreign key for table `{{%police}}`
        $this->dropForeignKey(
            '{{%fk-driver-police_id}}',
            '{{%driver}}'
        );

        // drops index for column `police_id`
        $this->dropIndex(
            '{{%idx-driver-police_id}}',
            '{{%driver}}'
        );

        $this->dropTable('{{%driver}}');
    }
}
