<?php

use yii\db\Migration;

/**
 * Haydovchi ismi/familiyasi EAI lookup orqali bot FSM'da allaqachon olinadi
 * (DriverStageHandler::finalizeDriver()), lekin hozirgacha lokal driver
 * jadvaliga yozilmagan — admin panelda polisga biriktirilgan haydovchini
 * faqat passport raqami bilan emas, ismi bilan ham ko'rsatish uchun.
 */
class m261015_100400_add_name_columns_to_driver_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%driver}}', 'firstName', $this->string()->null()->after('police_id'));
        $this->addColumn('{{%driver}}', 'lastName', $this->string()->null()->after('firstName'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%driver}}', 'lastName');
        $this->dropColumn('{{%driver}}', 'firstName');
    }
}
