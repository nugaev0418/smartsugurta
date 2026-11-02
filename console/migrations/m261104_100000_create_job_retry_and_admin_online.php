<?php

use yii\db\Migration;

/**
 * Navbat job'i (Gross/Uzbekinvest) barcha urinishdan so'ng muvaffaqiyatsiz bo'lsa,
 * admin "online" bo'lganda unga qayta urinish taklif qilinadi:
 *  - setting.admin_online — admin qo'lda yoqadi/o'chiradi (panelda);
 *  - job_retry — taklif qilingan qayta urinishlar (ariza ma'lumoti + holat).
 */
class m261104_100000_create_job_retry_and_admin_online extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%setting}}',
            'admin_online',
            $this->tinyInteger()->notNull()->defaultValue(0)->after('my_vehicles_status')
        );

        $this->createTable('{{%job_retry}}', [
            'id' => $this->primaryKey(),
            'chat_id' => $this->bigInteger()->notNull(),
            'application_data' => $this->text()->notNull(),
            'failed_provider' => $this->tinyInteger()->notNull(),
            'reason' => $this->string(512)->null(),
            // 0 = kutilmoqda, 1 = qayta yuborildi, 2 = vaqt tugadi, 3 = admin bekor qildi, 4 = qayta yuborish xatosi
            'status' => $this->tinyInteger()->notNull()->defaultValue(0),
            'retried_provider' => $this->tinyInteger()->null(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'resolved_at' => $this->timestamp()->null(),
        ]);
        $this->createIndex('{{%idx-job_retry-status}}', '{{%job_retry}}', 'status');
        $this->createIndex('{{%idx-job_retry-chat_id}}', '{{%job_retry}}', 'chat_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%job_retry}}');
        $this->dropColumn('{{%setting}}', 'admin_online');
    }
}
