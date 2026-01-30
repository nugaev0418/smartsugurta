<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%vehicle}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%police}}`
 */
class m260112_105532_create_vehicle_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%vehicle}}', [
            'id' => $this->primaryKey(),
            'licenseNumber' => $this->string(),
            'techPassportNumber' => $this->string(),
            'techPassportSeria' => $this->string(),
            'police_id' => $this->integer(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        // creates index for column `police_id`
        $this->createIndex(
            '{{%idx-vehicle-police_id}}',
            '{{%vehicle}}',
            'police_id'
        );

        // add foreign key for table `{{%police}}`
        $this->addForeignKey(
            '{{%fk-vehicle-police_id}}',
            '{{%vehicle}}',
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
            '{{%fk-vehicle-police_id}}',
            '{{%vehicle}}'
        );

        // drops index for column `police_id`
        $this->dropIndex(
            '{{%idx-vehicle-police_id}}',
            '{{%vehicle}}'
        );

        $this->dropTable('{{%vehicle}}');
    }
}
