<?php

use yii\db\Migration;

class m260605_150000_add_referral_to_botuser_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%botuser}}', 'referral_code', $this->string(20)->unique());
        $this->addColumn('{{%botuser}}', 'referred_by',   $this->integer()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%botuser}}', 'referral_code');
        $this->dropColumn('{{%botuser}}', 'referred_by');
    }
}
