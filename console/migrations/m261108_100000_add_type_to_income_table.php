<?php

use yii\db\Migration;

/**
 * income jadvali endi referal bonusidan tashqari sug'urta bonusini ham saqlaydi.
 * Mavjud yozuvlarning hammasi referal bonusi, shuning uchun standart qiymat 1.
 */
class m261108_100000_add_type_to_income_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%income}}',
            'type',
            $this->tinyInteger()->notNull()->defaultValue(1)->comment('1 - referal bonusi, 2 - sug\'urta bonusi')
        );
        $this->createIndex('idx_income_user_type', '{{%income}}', ['user_id', 'type']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_income_user_type', '{{%income}}');
        $this->dropColumn('{{%income}}', 'type');
    }
}
