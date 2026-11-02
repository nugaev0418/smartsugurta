<?php

use yii\db\Migration;

/**
 * Uzbekinvest "pauza" rejimi: pauza davomida bot Uzbekinvest'ga kirmaydi, shuning uchun admin shu
 * akkaunt bilan brauzerdan ishlay oladi (sayt bir akkauntda faqat bitta faol sessiyaga ruxsat beradi).
 * Boolean emas, vaqt: pauza unutilsa o'zi tugaydi.
 */
class m261105_100000_add_uzbekinvest_pause_until_to_setting_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%setting}}', 'uzbekinvest_pause_until', $this->dateTime()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%setting}}', 'uzbekinvest_pause_until');
    }
}
