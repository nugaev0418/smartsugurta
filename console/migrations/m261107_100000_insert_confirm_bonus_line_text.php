<?php

use yii\db\Migration;

/**
 * Tasdiqlash xabaridagi ("confirm texts") bonus qatori:
 * 1-%s = foiz, 2-%s = summa (OrderChannelNotifier::notify()).
 */
class m261107_100000_insert_confirm_bonus_line_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'confirm_bonus_line',
            'uz'      => "🎁 <b>Bonus (%s%%):</b> %s so'm",
            'ru'      => "🎁 <b>Бонус (%s%%):</b> %s сум",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'confirm_bonus_line']);
    }
}
