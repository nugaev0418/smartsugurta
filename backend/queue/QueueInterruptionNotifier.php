<?php

namespace backend\queue;

use backend\controllers\BotController;
use Yii;
use yii\base\Behavior;
use yii\queue\ExecEvent;
use yii\queue\Queue;

/**
 * Navbatdagi ish uzilib qolganini (ttr'da majburan o'ldirilgan yoki worker
 * to'xtagan) ko'rinadigan qiladi — ilgari bunday ish hech qanday iz
 * qoldirmasdan navbatdan o'chirilardi.
 *
 * Uzilgan ish QAYTA BAJARILMAYDI: polisa allaqachon yaratilgan bo'lishi
 * mumkin va qayta bajarish dublikat polisa bilan ikkinchi to'lov havolasini
 * yuborardi. Shuning uchun bu yerda faqat adminga xabar beriladi va ish
 * yopiladi — qolgani qo'lda hal qilinadi.
 */
class QueueInterruptionNotifier extends Behavior
{
    public function events(): array
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    public function beforeExec(ExecEvent $event): void
    {
        if ($event->attempt <= 1) {
            return;
        }

        $this->report(
            "🆘 Navbatdagi ish uzilib qolgan va QAYTA BAJARILMAYDI — qo'lda tekshirish kerak.\n"
            . 'Ish: ' . $this->jobName($event) . "\n"
            . "Urinish: {$event->attempt}"
        );

        // Queue::handleMessage() shuni ko'rib ishni bajarmasdan yopadi.
        $event->handled = true;
    }

    public function afterError(ExecEvent $event): void
    {
        $this->report(
            "❌ Navbatdagi ish xato bilan tugadi.\n"
            . 'Ish: ' . $this->jobName($event) . "\n"
            . "Urinish: {$event->attempt}\n"
            . ($event->error ? $event->error->getMessage() : "Xato matni yo'q")
        );
    }

    private function jobName(ExecEvent $event): string
    {
        return $event->job ? get_class($event->job) : 'noma\'lum';
    }

    private function report(string $text): void
    {
        Yii::error($text, 'queue');

        try {
            Yii::$app->telegram->sendMessage([
                'chat_id' => BotController::ADMIN_ID,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Yii::error('Navbat uzilishi haqida adminga xabar yuborilmadi: ' . $e->getMessage(), 'queue');
        }
    }
}
