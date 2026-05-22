<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%police}}`.
 */
class m260522_113617_add_anketa_id_column_to_police_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%police}}', 'provider_id', $this->integer());
        $this->addColumn('{{%police}}', 'anketa_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%police}}', 'provider_id');
        $this->dropColumn('{{%police}}', 'anketa_id');
    }
}
