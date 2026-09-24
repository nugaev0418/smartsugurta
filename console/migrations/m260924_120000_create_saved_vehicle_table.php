<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%saved_vehicle}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%botuser}}`
 */
class m260924_120000_create_saved_vehicle_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%saved_vehicle}}', [
            'id' => $this->primaryKey(),
            'botuser_id' => $this->integer()->notNull(),
            'gov_number' => $this->string(),
            'tech_passport_seria' => $this->string(3),
            'tech_passport_number' => $this->string(7),
            'owner_type' => $this->string(),
            'model' => $this->string()->null(),
            'vehicle_type_name' => $this->string()->null(),
            'ersp_check_status' => $this->string()->notNull()->defaultValue('idle'),
            'ersp_policies_json' => $this->text()->null(),
            'ersp_checked_at' => $this->dateTime()->null(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE NOW()'),
        ]);

        $this->createIndex(
            '{{%idx-saved_vehicle-botuser_id}}',
            '{{%saved_vehicle}}',
            'botuser_id'
        );

        $this->createIndex(
            '{{%idx-saved_vehicle-botuser_id-gov_number}}',
            '{{%saved_vehicle}}',
            ['botuser_id', 'gov_number'],
            true
        );

        $this->addForeignKey(
            '{{%fk-saved_vehicle-botuser_id}}',
            '{{%saved_vehicle}}',
            'botuser_id',
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
        $this->dropForeignKey(
            '{{%fk-saved_vehicle-botuser_id}}',
            '{{%saved_vehicle}}'
        );

        $this->dropIndex(
            '{{%idx-saved_vehicle-botuser_id-gov_number}}',
            '{{%saved_vehicle}}'
        );

        $this->dropIndex(
            '{{%idx-saved_vehicle-botuser_id}}',
            '{{%saved_vehicle}}'
        );

        $this->dropTable('{{%saved_vehicle}}');
    }
}
