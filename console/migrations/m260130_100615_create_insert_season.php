<?php

use yii\db\Migration;

class m260130_100615_create_insert_season extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("INSERT INTO `seasonalInsurance` (`id`, `name`, `days`, `seasonId`) VALUES
(1, '1 yil', 365, '8465a831-850f-4445-a995-ef71195094ab'),
(2, '6 oy', 180, '9848096e-cc12-4dbd-893b-41f2cdfc9a0e'),
(3, '20 kun', 20, '0d546748-0ba6-43bc-9ce2-1b977ad9e494');");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260130_100615_create_insert_season cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260130_100615_create_insert_season cannot be reverted.\n";

        return false;
    }
    */
}
