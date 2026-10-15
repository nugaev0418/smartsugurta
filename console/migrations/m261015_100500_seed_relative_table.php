<?php

use backend\component\RelativeType;
use yii\db\Migration;

/**
 * `relative` jadvali hech qachon seed qilinmagan edi (driver.relativeId uni
 * FK sifatida ishlatadi, lekin topilmasa ham xavfsiz — Driver model qoidasi
 * skipOnError=>true). RelativeType::eaiId() dagi 0-10 kanonik kodlarga mos
 * qatorlarni qo'shadi, shu bilan admin panelda haydovchi-qarindoshlik nomi
 * (Relative::name) ko'rinadigan bo'ladi.
 */
class m261015_100500_seed_relative_table extends Migration
{
    private const NAMES = [
        RelativeType::NOT_RELATED => "Qarindosh emas",
        RelativeType::FATHER => 'Ota',
        RelativeType::MOTHER => 'Ona',
        RelativeType::HUSBAND => 'Er',
        RelativeType::WIFE => 'Xotin',
        RelativeType::SON => "O'g'il",
        RelativeType::DAUGHTER => 'Qiz',
        RelativeType::OLDER_BROTHER => 'Katta aka',
        RelativeType::YOUNGER_BROTHER => 'Kichik aka',
        RelativeType::OLDER_SISTER => 'Katta opa',
        RelativeType::YOUNGER_SISTER => 'Kichik opa',
    ];

    public function safeUp()
    {
        foreach (self::NAMES as $code => $name) {
            $relativeId = RelativeType::eaiId($code);

            $exists = (new \yii\db\Query())
                ->from('{{%relative}}')
                ->where(['relativeId' => $relativeId])
                ->exists($this->db);

            if (!$exists) {
                $this->insert('{{%relative}}', [
                    'name' => $name,
                    'relativeId' => $relativeId,
                ]);
            }
        }
    }

    public function safeDown()
    {
        foreach (self::NAMES as $code => $name) {
            $this->delete('{{%relative}}', ['relativeId' => RelativeType::eaiId($code)]);
        }
    }
}
