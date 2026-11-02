<?php

use yii\db\Migration;

/**
 * gross_api_log endi barcha provayderlar (Gross, Uzbekinvest) job so'rov/javoblarini
 * saqlaydi ("Job logs"). Mavjud yozuvlar Gross (2) bo'lib qoladi.
 */
class m261103_100000_add_provider_id_to_gross_api_log_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%gross_api_log}}',
            'provider_id',
            $this->tinyInteger()->notNull()->defaultValue(2)->after('id')
        );
        $this->createIndex('{{%idx-gross_api_log-provider_id}}', '{{%gross_api_log}}', 'provider_id');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%idx-gross_api_log-provider_id}}', '{{%gross_api_log}}');
        $this->dropColumn('{{%gross_api_log}}', 'provider_id');
    }
}
