<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%police}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%botuser}}`
 * - `{{%season}}`
 */
class m260109_115206_create_police_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%police}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'policeId' => $this->string(),
            'startAt' => $this->date(),
            'endAt' => $this->date(),
            'pdfUrl' => $this->string(),
            'status' => $this->integer()->defaultValue(0),
            'paymentId' => $this->string(),
            'paymentLink' => $this->string(),
            'gateway' => $this->string(),
            'amount' => $this->integer(),
            'driverRestriction' => $this->integer(),
            'season_id' => $this->integer(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        // creates index for column `user_id`
        $this->createIndex(
            '{{%idx-police-user_id}}',
            '{{%police}}',
            'user_id'
        );

        // add foreign key for table `{{%botuser}}`
        $this->addForeignKey(
            '{{%fk-police-user_id}}',
            '{{%police}}',
            'user_id',
            '{{%botuser}}',
            'id',
            'CASCADE'
        );

        // creates index for column `season_id`
        $this->createIndex(
            '{{%idx-police-season_id}}',
            '{{%police}}',
            'season_id'
        );

        // add foreign key for table `{{%season}}`
        $this->addForeignKey(
            '{{%fk-police-season_id}}',
            '{{%police}}',
            'season_id',
            '{{%season}}',
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
            '{{%fk-police-user_id}}',
            '{{%police}}'
        );

        // drops index for column `user_id`
        $this->dropIndex(
            '{{%idx-police-user_id}}',
            '{{%police}}'
        );

        // drops foreign key for table `{{%season}}`
        $this->dropForeignKey(
            '{{%fk-police-season_id}}',
            '{{%police}}'
        );

        // drops index for column `season_id`
        $this->dropIndex(
            '{{%idx-police-season_id}}',
            '{{%police}}'
        );

        $this->dropTable('{{%police}}');
    }
}
