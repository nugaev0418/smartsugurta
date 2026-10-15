<?php

namespace backend\component;

use common\models\Botuser;
use common\models\Deeplink;

class DeeplinkService
{
    private MarketingAttributionService $attribution;

    public function __construct(?MarketingAttributionService $attribution = null)
    {
        $this->attribution = $attribution ?? new MarketingAttributionService();
    }

    public function processStartCommand(string $text, string $chatId, bool $isNewUser): bool
    {
        if (!preg_match('/^\/start (dl\w+)$/', $text, $m)) {
            return false;
        }

        $deeplink = Deeplink::findOne(['code' => $m[1]]);
        $self     = Botuser::find()->where(['chat_id' => $chatId])->one();

        if ($deeplink && $self && $isNewUser) {
            $self->deeplink_code = $deeplink->code;
            $self->save(false);
        }

        $this->attribution->recordDeeplinkTouch($chatId, $deeplink, $isNewUser);

        return true;
    }
}
