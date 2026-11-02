<?php

use yii\db\Migration;

/**
 * Polisa yaratiladigan provayderni admin paneldan tanlash:
 * 2 = Gross (standart), 3 = Uzbekinvest (Police::PROVIDER_*).
 */
class m261102_100000_add_osago_provider_to_setting_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%setting}}',
            'osago_provider',
            $this->tinyInteger()->notNull()->defaultValue(2)->after('my_vehicles_status')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%setting}}', 'osago_provider');
    }
}
