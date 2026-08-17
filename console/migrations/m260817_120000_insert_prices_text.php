<?php

use yii\db\Migration;

class m260817_120000_insert_prices_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'Prices',
            'uz'      => "💰 Narxlar",
            'ru'      => "💰 Цены",
        ]);
        $this->insert('{{%text}}', [
            'keyword' => 'prices page message',
            'uz'      => "Narxlar tez orada e'lon qilinadi.",
            'ru'      => "Цены будут объявлены в ближайшее время.",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'Prices']);
        $this->delete('{{%text}}', ['keyword' => 'prices page message']);
    }
}
