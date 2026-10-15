<?php

use yii\db\Migration;

/**
 * Attribution hodisalari tarixi (start/campaign_visit/survey_answered/
 * purchase). police_id faqat 'purchase' hodisasi uchun to'ldiriladi va
 * UNIQUE (MySQL'da bo'sh/NULL qiymatlar cheklanmaydi) orqali bitta polis
 * uchun takroriy konversiya yozuvini DB darajasida oldini oladi.
 */
class m261015_100200_create_marketing_touch_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%marketing_touch}}', [
            'id'         => $this->primaryKey(),
            'user_id'    => $this->integer()->notNull(),
            'event'      => $this->string(20)->notNull(),
            'source'     => $this->string(30)->null(),
            'campaign'   => $this->string(20)->null(),
            'method'     => $this->string(20)->null(),
            'police_id'  => $this->integer()->null(),
            'metadata'   => $this->json(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->createIndex('{{%idx-marketing_touch-user_id}}', '{{%marketing_touch}}', 'user_id');
        $this->addForeignKey(
            '{{%fk-marketing_touch-user_id}}',
            '{{%marketing_touch}}',
            'user_id',
            '{{%botuser}}',
            'id',
            'CASCADE'
        );

        $this->createIndex('{{%idx-marketing_touch-police_id}}', '{{%marketing_touch}}', 'police_id', true);
        $this->addForeignKey(
            '{{%fk-marketing_touch-police_id}}',
            '{{%marketing_touch}}',
            'police_id',
            '{{%police}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-marketing_touch-police_id}}', '{{%marketing_touch}}');
        $this->dropForeignKey('{{%fk-marketing_touch-user_id}}', '{{%marketing_touch}}');
        $this->dropTable('{{%marketing_touch}}');
    }
}
