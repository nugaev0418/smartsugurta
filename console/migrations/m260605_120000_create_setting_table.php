<?php

use yii\db\Migration;

class m260605_120000_create_setting_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%setting}}', [
            'id'                    => $this->primaryKey(),
            'bot_status'            => $this->tinyInteger()->notNull()->defaultValue(1),
            'police_status'         => $this->tinyInteger()->notNull()->defaultValue(1),
            'payment_status'        => $this->tinyInteger()->notNull()->defaultValue(1),
            'user_percent'          => $this->integer()->notNull()->defaultValue(0),
            'tashkent_user_percent' => $this->integer()->notNull()->defaultValue(0),
            'referral_percent'      => $this->integer()->notNull()->defaultValue(0),
        ]);

        // Yagona yozuv
        $this->insert('{{%setting}}', [
            'bot_status'            => 1,
            'police_status'         => 1,
            'payment_status'        => 1,
            'user_percent'          => 0,
            'tashkent_user_percent' => 0,
            'referral_percent'      => 0,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%setting}}');
    }
}
