<?php

use yii\db\Migration;

/**
 * "🚗 Mening avtolarim" bo'limini hammaga (admin bo'lmaganlarga ham)
 * ochish/yopish uchun global bayroq — Setting::getMyVehiclesStatus().
 * Standart holat 0 (yopiq) — admin panelda "Bot sozlamalari"dan ataylab
 * yoqilishi kerak.
 */
class m260924_140000_add_my_vehicles_status_to_setting_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%setting}}',
            'my_vehicles_status',
            $this->tinyInteger()->notNull()->defaultValue(0)->after('payment_status')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%setting}}', 'my_vehicles_status');
    }
}
