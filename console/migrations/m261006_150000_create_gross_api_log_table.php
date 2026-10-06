<?php

use yii\db\Migration;

/**
 * Gross Insurance integratsiyasining har bir qadamidagi (vehicle/owner/
 * driver/phone-checker/contract) so'rov va javobni admin panelda ko'rish
 * uchun. `Police` yaratilishidan OLDIN, hatto muvaffaqiyatsiz urinishlarda
 * ham yoziladi — shuning uchun FK yo'q, `police_id` muvaffaqiyatli bo'lsa
 * keyinroq to'ldiriladi.
 */
class m261006_150000_create_gross_api_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%gross_api_log}}', [
            'id' => $this->primaryKey(),
            'chat_id' => $this->bigInteger()->null(),
            'run_id' => $this->string(32)->notNull(),
            'label' => $this->string(64)->notNull(),
            'attempt' => $this->integer()->notNull()->defaultValue(1),
            'success' => $this->boolean()->notNull()->defaultValue(false),
            'request' => $this->text()->null(),
            'response' => $this->text()->null(),
            'error_message' => $this->string(512)->null(),
            'police_id' => $this->integer()->null(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->createIndex('{{%idx-gross_api_log-run_id}}', '{{%gross_api_log}}', 'run_id');
        $this->createIndex('{{%idx-gross_api_log-chat_id}}', '{{%gross_api_log}}', 'chat_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%gross_api_log}}');
    }
}
