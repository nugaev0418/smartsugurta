<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%police}}`.
 */
class m260123_103739_add_payment_status_column_to_police_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%police}}', 'payment_status', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%police}}', 'payment_status');
    }
}
