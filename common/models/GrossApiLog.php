<?php

namespace common\models;

/**
 * @property int $id
 * @property int|null $chat_id
 * @property string $run_id
 * @property string $label
 * @property int $attempt
 * @property bool $success
 * @property string|null $request
 * @property string|null $response
 * @property string|null $error_message
 * @property int|null $police_id
 * @property string|null $created_at
 *
 * @property Police|null $police
 */
class GrossApiLog extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'gross_api_log';
    }

    public function rules()
    {
        return [
            [['run_id', 'label'], 'required'],
            [['chat_id', 'attempt', 'police_id'], 'integer'],
            [['success'], 'boolean'],
            [['request', 'response'], 'string'],
            [['run_id'], 'string', 'max' => 32],
            [['label'], 'string', 'max' => 64],
            [['error_message'], 'string', 'max' => 512],
            [['created_at'], 'safe'],
        ];
    }

    public function getPolice()
    {
        return $this->hasOne(Police::class, ['id' => 'police_id']);
    }
}
