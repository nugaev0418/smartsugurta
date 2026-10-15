<?php

use yii\db\Migration;

/**
 * Har bir Telegram foydalanuvchisining joriy marketing-attribution holati —
 * bitta qator/foydalanuvchi (botuser.id bo'yicha, boshqa barcha related
 * jadvallar bilan bir xil konventsiya). first_* va last_* ustunlari kampaniya
 * kodini (deeplink.code) snapshot sifatida saqlaydi — FK emas, chunki
 * kampaniya keyinroq o'chirilsa ham tarixiy attribution o'zgarmasligi kerak.
 */
class m261015_100100_create_marketing_attribution_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%marketing_attribution}}', [
            'id'             => $this->primaryKey(),
            'user_id'        => $this->integer()->notNull(),
            'first_source'   => $this->string(30)->null(),
            'first_campaign' => $this->string(20)->null(),
            'last_source'    => $this->string(30)->null(),
            'last_campaign'  => $this->string(20)->null(),
            'source_method'  => $this->string(20)->null(),
            'first_seen_at'  => $this->timestamp()->defaultExpression('NOW()'),
            'last_seen_at'   => $this->timestamp()->defaultExpression('NOW()'),
            'survey_source'  => $this->string(30)->null(),
            'survey_at'      => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%idx-marketing_attribution-user_id}}', '{{%marketing_attribution}}', 'user_id', true);
        $this->addForeignKey(
            '{{%fk-marketing_attribution-user_id}}',
            '{{%marketing_attribution}}',
            'user_id',
            '{{%botuser}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-marketing_attribution-user_id}}', '{{%marketing_attribution}}');
        $this->dropTable('{{%marketing_attribution}}');
    }
}
