<?php

use yii\db\Migration;

/**
 * "🚗 Mening avtolarim" bo'limi (backend/component/bot/stage/MyVehiclesStageHandler.php,
 * backend/queue/ErspLookupJob.php) uchun uz/ru matnlari. Bo'lim boshida hammasi
 * hardcoded uzbek matn sifatida yozilgan edi (getMText()/Text jadvali ishlatilmagan),
 * shuning uchun ruscha tilni tanlagan foydalanuvchilarga tarjima qilinmagan holda
 * chiqar edi.
 */
class m260924_130000_insert_my_vehicles_text extends Migration
{
    private array $rows = [
        [
            'keyword' => 'My vehicles menu button',
            'uz' => "🚗 Mening avtolarim",
            'ru' => "🚗 Мои автомобили",
        ],
        [
            'keyword' => 'my vehicles add button',
            'uz' => "➕ Avtomobil qo'shish",
            'ru' => "➕ Добавить автомобиль",
        ],
        [
            'keyword' => 'my vehicles back to list button',
            'uz' => "⬅️ Ro'yxatga qaytish",
            'ru' => "⬅️ Назад к списку",
        ],
        [
            'keyword' => 'my vehicles check button',
            'uz' => "Tekshirish",
            'ru' => "Проверить",
        ],
        [
            'keyword' => 'my vehicles new insurance button',
            'uz' => "Yangi sug'urta qilish",
            'ru' => "Оформить новую страховку",
        ],
        [
            'keyword' => 'my vehicles delete button',
            'uz' => "🗑 Avtomobilni o'chirish",
            'ru' => "🗑 Удалить автомобиль",
        ],
        [
            'keyword' => 'my vehicles delete confirm button',
            'uz' => "✅ Ha, o'chirish",
            'ru' => "✅ Да, удалить",
        ],
        [
            'keyword' => 'my vehicles delete cancel button',
            'uz' => "❌ Yo'q, bekor qilish",
            'ru' => "❌ Нет, отменить",
        ],
        [
            'keyword' => 'my vehicles list title',
            'uz' => "Saqlangan avtomobillaringiz:",
            'ru' => "Ваши сохранённые автомобили:",
        ],
        [
            'keyword' => 'my vehicles list empty',
            'uz' => "Hali avtomobil saqlanmagan. \"%s\" orqali qo'shing.",
            'ru' => "Автомобиль пока не сохранён. Добавьте через «%s».",
        ],
        [
            'keyword' => 'my vehicles not found',
            'uz' => "Avtomobil topilmadi, ma'lumotlarni tekshirib qayta urinib ko'ring.",
            'ru' => "Автомобиль не найден, проверьте данные и попробуйте снова.",
        ],
        [
            'keyword' => 'my vehicles saved',
            'uz' => "✅ %s avtomobili saqlandi.",
            'ru' => "✅ Автомобиль %s сохранён.",
        ],
        [
            'keyword' => 'my vehicles checking',
            'uz' => "🔎 Tekshirilmoqda, natija tez orada shu yerga yuboriladi...",
            'ru' => "🔎 Идёт проверка, результат скоро появится здесь...",
        ],
        [
            'keyword' => 'my vehicles active policies title',
            'uz' => "Amaldagi sug'urtalar:",
            'ru' => "Действующие страховки:",
        ],
        [
            'keyword' => 'my vehicles no policies',
            'uz' => "Amaldagi sug'urta topilmadi yoki hali tekshirilmagan.",
            'ru' => "Действующая страховка не найдена или ещё не проверялась.",
        ],
        [
            'keyword' => 'my vehicles next check in',
            'uz' => "⏳ Keyingi tekshirish %d daqiqadan keyin mumkin.",
            'ru' => "⏳ Следующая проверка возможна через %d минут.",
        ],
        [
            'keyword' => 'my vehicles delete confirm question',
            'uz' => "⚠️ Rostdan ham %s avtomobilini ro'yxatdan o'chirmoqchimisiz?",
            'ru' => "⚠️ Вы действительно хотите удалить автомобиль %s из списка?",
        ],
        [
            'keyword' => 'my vehicles deleted',
            'uz' => "🗑 %s avtomobili ro'yxatdan o'chirildi.",
            'ru' => "🗑 Автомобиль %s удалён из списка.",
        ],
        [
            'keyword' => 'my vehicles unknown company',
            'uz' => "Noma'lum kompaniya",
            'ru' => "Неизвестная компания",
        ],
        [
            'keyword' => 'my vehicles unknown period',
            'uz' => "muddat noma'lum",
            'ru' => "срок неизвестен",
        ],
        [
            'keyword' => 'my vehicles unknown date',
            'uz' => "noma'lum",
            'ru' => "неизвестно",
        ],
        [
            'keyword' => 'my vehicles policy expires label',
            'uz' => "Tugash sanasi:",
            'ru' => "Дата окончания:",
        ],
        [
            'keyword' => 'my vehicles view policy link',
            'uz' => "Polisni ko'rish",
            'ru' => "Посмотреть полис",
        ],
        [
            'keyword' => 'my vehicles check failed',
            'uz' => "⚠️ %s avtomobili bo'yicha sug'urta tekshiruvi muvaffaqiyatsiz bo'ldi, keyinroq qayta urinib ko'ring.",
            'ru' => "⚠️ Проверка страховки автомобиля %s не удалась, попробуйте позже.",
        ],
        [
            'keyword' => 'my vehicles no active policies notify',
            'uz' => "🚗 %s: amaldagi sug'urta polisasi topilmadi.",
            'ru' => "🚗 %s: действующий полис не найден.",
        ],
        [
            'keyword' => 'my vehicles active policies notify title',
            'uz' => "🚗 %s bo'yicha amaldagi sug'urtalar:",
            'ru' => "🚗 Действующие страховки по %s:",
        ],
    ];

    public function safeUp()
    {
        foreach ($this->rows as $row) {
            $this->insert('{{%text}}', $row);
        }
    }

    public function safeDown()
    {
        foreach ($this->rows as $row) {
            $this->delete('{{%text}}', ['keyword' => $row['keyword']]);
        }
    }
}
