<?php

use yii\db\Migration;

/**
 * Deeplinksiz (sof /start) kelgan yangi foydalanuvchiga bir martalik
 * ko'rsatiladigan "qayerdan bildingiz" so'rovnomasi matnlari.
 */
class m261015_100600_insert_survey_source_text extends Migration
{
    private const ROWS = [
        'How did you find us?' => [
            'uz' => "Smart Sug'urta Botni qayerdan topdingiz?",
            'ru' => "Где вы узнали о Smart Sug'urta Bot?",
        ],
        'From Instagram blogger' => [
            'uz' => 'Instagram blogeridan',
            'ru' => 'От блогера в Instagram',
        ],
        'From Instagram ads' => [
            'uz' => 'Instagram reklamasi',
            'ru' => 'Реклама в Instagram',
        ],
        'From Telegram channel' => [
            'uz' => 'Telegram kanalidan',
            'ru' => 'Из Telegram-канала',
        ],
        'Friend recommended' => [
            'uz' => "Do'stim tavsiya qildi",
            'ru' => 'Друг порекомендовал',
        ],
        'From Google or other' => [
            'uz' => 'Google yoki boshqa manba',
            'ru' => 'Google или другой источник',
        ],
        'Other, do not remember' => [
            'uz' => "Boshqa / eslay olmayman",
            'ru' => 'Другое / не помню',
        ],
    ];

    public function safeUp()
    {
        foreach (self::ROWS as $keyword => $lang) {
            $this->insert('{{%text}}', [
                'keyword' => $keyword,
                'uz'      => $lang['uz'],
                'ru'      => $lang['ru'],
            ]);
        }
    }

    public function safeDown()
    {
        foreach (array_keys(self::ROWS) as $keyword) {
            $this->delete('{{%text}}', ['keyword' => $keyword]);
        }
    }
}
