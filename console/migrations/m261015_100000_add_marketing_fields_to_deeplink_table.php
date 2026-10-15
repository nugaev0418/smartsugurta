<?php

use yii\db\Migration;

/**
 * `deeplink` jadvali kampaniya havolalarini (`?start=dl<code>`) allaqachon
 * boshqaradi — bu yerda uni to'liq marketing-kampaniya sifatida ishlatish
 * uchun kanal/manba/xarajat/muddat maydonlari qo'shiladi. Mavjud qatorlar
 * (`name`/`code`) tegilmaydi.
 */
class m261015_100000_add_marketing_fields_to_deeplink_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%deeplink}}', 'channel', $this->string(30)->null()->after('code'));
        $this->addColumn('{{%deeplink}}', 'source', $this->string(30)->null()->after('channel'));
        $this->addColumn('{{%deeplink}}', 'cost', $this->decimal(12, 2)->notNull()->defaultValue(0)->after('source'));
        $this->addColumn('{{%deeplink}}', 'currency', $this->string(3)->notNull()->defaultValue('UZS')->after('cost'));
        $this->addColumn('{{%deeplink}}', 'starts_at', $this->date()->null()->after('currency'));
        $this->addColumn('{{%deeplink}}', 'ends_at', $this->date()->null()->after('starts_at'));
        $this->addColumn('{{%deeplink}}', 'status', $this->tinyInteger()->notNull()->defaultValue(1)->after('ends_at'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%deeplink}}', 'status');
        $this->dropColumn('{{%deeplink}}', 'ends_at');
        $this->dropColumn('{{%deeplink}}', 'starts_at');
        $this->dropColumn('{{%deeplink}}', 'currency');
        $this->dropColumn('{{%deeplink}}', 'cost');
        $this->dropColumn('{{%deeplink}}', 'source');
        $this->dropColumn('{{%deeplink}}', 'channel');
    }
}
