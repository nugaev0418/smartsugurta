<?php

use yii\db\Migration;

class m261111_100000_insert_osago_no_policy_reminder_text extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%text}}', [
            'keyword' => 'osago_no_policy_reminder',
            'uz' => "⚠️ <b>%s</b> raqamli transportingizning amaldagi sug'urtasi (OSAGO) topilmadi. Sug'urtasiz yurish jarima bilan xavfli — botda osongina sug'urta qilib oling!",
            'ru' => "⚠️ Действующий полис ОСАГО для вашего транспортного средства с номером <b>%s</b> не найден. Езда без страховки чревата штрафом — оформите страховку легко через бота!",
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%text}}', ['keyword' => 'osago_no_policy_reminder']);
    }
}
