<?php

use yii\db\Migration;

class m260609_120000_create_deeplink_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%deeplink}}', [
            'id'         => $this->primaryKey(),
            'name'       => $this->string(255)->notNull(),
            'code'       => $this->string(20)->notNull()->unique(),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        $this->addColumn('{{%botuser}}', 'deeplink_code', $this->string(20)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%botuser}}', 'deeplink_code');
        $this->dropTable('{{%deeplink}}');
    }
}
