<?php

namespace common\models;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $created_at
 */
class Deeplink extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'deeplink';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 20],
            [['code'], 'unique'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Nom',
            'code'       => 'Kod',
            'created_at' => 'Yaratilgan',
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && empty($this->code)) {
            $this->code = self::generateCode();
        }
        return parent::beforeSave($insert);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'dl' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::find()->where(['code' => $code])->exists());
        return $code;
    }

    public function getClickCount(): int
    {
        return (int)Botuser::find()->where(['deeplink_code' => $this->code])->count();
    }

    public function getLink(): string
    {
        return "https://t.me/" . \backend\controllers\BotController::BOT_USERNAME . "?start=" . $this->code;
    }
}
