<?php

namespace backend\component\bot;

use common\models\Botuser;

/**
 * Reads/writes the Botuser.data JSON column exactly the way BotController's
 * old getDataArray()/saveData()/getKeyValue()/setKeyValue() did — same
 * keys, same JSON shape, same JSON_UNESCAPED_UNICODE flag. Production has
 * live conversations mid-flight in this format, so the format itself must
 * not change, only where the code that reads/writes it lives.
 */
class BotUserState
{
    private string $chatId;
    private ?array $dataCache = null;

    public function __construct(string $chatId)
    {
        $this->chatId = $chatId;
    }

    public function get(string $key, $default = '')
    {
        $data = $this->getDataArray();
        return $data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $data = $this->getDataArray();
        $data[$key] = $value;
        $this->saveData($data);
    }

    public function getPage(): string
    {
        return (string) $this->get('page');
    }

    public function setPage(string $page): void
    {
        $this->set('page', $page);
    }

    private function getDataArray(): array
    {
        if ($this->dataCache !== null) {
            return $this->dataCache;
        }

        $user = Botuser::find()
            ->select(['data'])
            ->where(['chat_id' => $this->chatId])
            ->one();

        return $this->dataCache = $user && $user->data
            ? json_decode($user->data, true)
            : [];
    }

    private function saveData(array $data): void
    {
        $this->dataCache = $data;

        $user = Botuser::findOne(['chat_id' => $this->chatId]);
        $user->data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $user->save(false);
    }
}
