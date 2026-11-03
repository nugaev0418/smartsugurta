<?php

use yii\db\Migration;

/**
 * Avtomobil/egasi/haydovchi tekshiruvi va narx hisoblash provayderini admin paneldan
 * tanlash: 1 = EuroAsia (standart), 2 = Kafolat (Setting::LOOKUP_*).
 */
class m261106_100000_add_lookup_provider_to_setting_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%setting}}',
            'lookup_provider',
            $this->tinyInteger()->notNull()->defaultValue(1)->after('osago_provider')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%setting}}', 'lookup_provider');
    }
}
