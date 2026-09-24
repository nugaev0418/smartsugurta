# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Loyiha haqida

Bu — **Yii2 Advanced Project Template** asosidagi PHP ilova bo'lib, Telegram bot orqali avtomobil OSAGO
sug'urta polisi sotadigan tizim (`smartsugurtabot`). Polisalar ikkita tashqi provayder orqali
rasmiylashtiriladi — **EuroAsia (EAI)** GraphQL API va **Gross Insurance** (login/captcha/cookie asosida
"screen-scraping" qiluvchi klient), to'lovlar esa **Paynet** (`flagship.paynet.uz`) orqali amalga oshiriladi.
Loyihada uchta standart Yii2 tier bor: `common`, `backend` (admin panel + bot webhook + Mini App API),
`frontend` (deyarli ishlatilmaydi, faqat `SiteController`), `console` (queue worker/migratsiya).

## Ishlab chiqish buyruqlari

Barcha buyruqlar loyiha ildizidan (`composer.json` joylashgan joydan) ishga tushiriladi.

```bash
composer install                     # bog'liqliklarni o'rnatish

php init --env=Development            # environments/dev dan config fayllarini nusxalash (birinchi sozlashda)

php yii migrate                       # console/migrations dagi migratsiyalarni qo'llash
php yii migrate/create <name>         # yangi migratsiya yaratish

php yii queue/listen                  # default queue kanalini tinglash (agar kerak bo'lsa)
php yii paynetQueue/listen             # Paynet to'lov navbatini ishga tushirish
php yii grossQueue/listen              # Gross Insurance polisa navbatini ishga tushirish
php yii broadcastQueue/listen          # bot orqali ommaviy xabar yuborish navbati
php yii erspQueue/listen               # "Mening avtolarim" ERSP sug'urta tekshiruvi navbati
```

### Testlar (Codeception + PHPUnit)

```bash
codecept build                        # test yordamchi klasslarni generatsiya qilish
codecept run                          # BARCHA app'lar (common, frontend, backend) testlarini ishga tushirish
codecept run unit                     # faqat unit testlar
codecept run unit UserTest            # bitta test klassi
codecept run unit UserTest:testName   # bitta test metodi
```

Test yuritish uchun alohida bootstrap fayli bor: `yii_test` (`YII_ENV=test`, `common/config/test*.php` va
`console/config/test*.php` fayllarini qo'shib yuklaydi). Har bir tier (`common`, `frontend`, `backend`) o'z
`codeception.yml`iga ega; ildizdagi `codeception.yml` ularning barchasini birlashtiradi.

### Muhit o'zgaruvchilari

`.env` faylida (`.env.example`dan nusxa) `TELEGRAM_BOT_TOKEN` bo'lishi kerak — `common/eleirbag/Telegram`
komponenti (`telegram` ilova komponenti sifatida ro'yxatdan o'tgan) shu orqali ishlaydi. DB ulanishi
`common/config/main-local.php` da (MySQL 8.0, DB nomi `smart`).

## Arxitektura

### Telegram bot — yagona webhook, registry-asosidagi FSM (holat mashinasi)

`backend/controllers/BotController.php` (~220 qator) — endi faqat **dispatcher**: webhook so'rovini
qabul qiladi, umumiy infratuzilmani (`BotMessenger`, `BotTextService`, `BotUserState`, `WebhookGuard`,
`BotUserOnboarding`) ishga tushiradi, `BotContext`ni quradi va uni `BotCommandRouter`/`BotStageRegistry`ga
uzatadi. Bot logikasining o'zi `backend/component/bot/` papkasida, mas'uliyatlarga bo'lingan holda yashaydi:

- **`BotContext`** (`backend/component/bot/BotContext.php`) — bitta webhook so'rovi davomida stage-handler
  klasslarga uzatiladigan obyekt: `chat_id`/`text`/`data`/`telegram` haqiqiy propertylar, qolgan hamma narsa
  (`page`, `phone`, `lisenceNumber`, `drivers`, `lang` va h.k.) `__get`/`__set` magic metodlari orqali
  **bitta JSON blob**ga o'qiladi/yoziladi — `Botuser.data` ustunida, `chat_id` bo'yicha
  (`backend/component/bot/BotUserState.php`). Yangi maydon qo'shish uchun alohida DB ustuni kerak emas —
  shunchaki `$ctx->newField` deb yozsa bo'ladi (format eski koddan o'zgarmagan, faqat joyi ko'chgan).
- **Sahifa konstantalari** `backend/models/Pages.php` da (`Pages::PHONE`, `Pages::CONFIRM_PAGE` va h.k.)
  aniqlangan.
- **Dispatch**: `actionStart()` avval `BotCommandRouter::route($ctx)`ni chaqiradi — bu joriy sahifadan
  qat'i nazar ishlaydigan global buyruqlarni (Main menu, Wallet, Support, admin tugmalari va h.k.)
  tekshiradi. Agar hech biri mos kelmasa, `BotStageRegistry::get($ctx->page)->handle($ctx)` orqali joriy
  sahifaga mos stage-handler klassga yo'naltiriladi.
- **Naqsh — stage-handler klasslar** (`backend/component/bot/stage/`): har bir `Pages::X` (yoki bir nechta
  yaqin bog'liq sahifa, masalan `VehicleLookupStageHandler` uchtasini — `LISENCE_NUMBER`/`TEXPASS_SERIA`/
  `TEXPASS_NUMBER`— bitta klassda boshqaradi) `BotStageInterface`ni amalga oshiradi: `show(BotContext $ctx,
  array $params = [])` (klaviaturani ko'rsatadi, `$ctx->page`ni saqlaydi) va `handle(BotContext $ctx)`
  (foydalanuvchi javobini tekshiradi, keyingi bosqichga o'tkazadi). Bosqichlar orasidagi o'tish **to'g'ridan-
  to'g'ri konstruktor orqali in'ektsiya qilingan bog'liqliklar** orqali amalga oshiriladi (masalan
  `PhoneStageHandler` konstruktorida `VehicleLookupStageHandler`ni oladi) — `Pages::`ga asoslangan yashirin
  registry-qidiruv emas, shuning uchun "kim kimni chaqiradi" kod o'qishda darhol ko'rinadi.
  **Yangi bosqich qo'shish**: (1) `Pages::` konstantasi qo'shish, (2) yangi `*StageHandler` klass yozish
  (yoki mavjudiga metod qo'shish), (3) `BotController::buildStageRegistry()`ga bitta `register()` qatori
  qo'shish — `actionStart()`ning o'ziga tegilmaydi.
- **Umumiy klasslar**: `BotMessenger` (barcha Telegram-yuborish operatsiyalari), `BotTextService`
  (`Text` modelidan ko'p tilli matn — `$ctx->getMText($keyword)`/`getKeywordText($text)`, tugma matnini
  kod ichida hardcode qilmang), `WebhookGuard` (update-takrorlanish keshi + chat-bo'yicha mutex-qulf),
  `BotUserOnboarding` (yangi foydalanuvchi yaratish + tarix yozish).
- **Bir vaqtda bitta so'rov**: har bir `chat_id` uchun `Yii::$app->mutex` orqali lock olinadi
  (`WebhookGuard::tryLock`) — parallel xabarlar navbatga tushadi, ikkinchisi "so'rovingiz ishlanmoqda" deb
  javob qaytaradi. Telegramning qayta yuborgan (`update_id` takrorlangan) webhooklari cache orqali
  filtrlanadi.
- **Texnik ishlar rejimi**: `Setting::getBotStatus()`/`getPoliceStatus()`/`getPaymentStatus()` orqali bot,
  polisa yaratish va to'lov qismlarini alohida-alohida o'chirib qo'yish mumkin (admin paneldan).

### Telegram Mini App (WebApp) — botdan mustaqil, xizmatlarni qayta ishlatuvchi JSON API

`backend/controllers/WebAppController.php` — Telegram Mini App uchun JSON API (`backend/web/webapp/` da
frontend joylashgan). Bot bilan umumiy biznes qoidalari `backend/component/insurance/` papkasidagi
servislarga chiqarilgan va ikkalasi ham shulardan foydalanadi: `SeasonalInsuranceCatalog` (3 mavsum —
GUID/kun/`period_type`, yagona manba), `VehicleLookupService`/`OwnerLookupService`/`DriverLookupService`
(`EuroAsiaService` ustidan normalizatsiya qatlami), `OsagoRequestBuilder` (`OsagoApplicationData` DTO'dan
EAI va Gross so'rov massivlarini quradi) va `OsagoSubmissionService` (EAI'ga to'g'ridan-to'g'ri yuborish
yoki Gross navbatiga qo'yish qarori + `Police` yozuvini saqlash). WebApp o'zining alohida oqimiga ega
(bosqichma-bosqich `actionInit` → `actionVehicle` → `actionOwner` → `actionDriver` → `actionCalculate` →
`actionSubmit` endpointlar, `Pages::` FSM'siz — har so'rovda kerakli ma'lumot to'liq yuboriladi).

**Muhim**: `OsagoSubmissionService::submit()` Toshkent (`01`/`10`) davlat raqamlarini EAI'ga to'g'ridan-
to'g'ri yuborish-yubormaslikni CHAQIRUVCHIDAN oladi (`$allowDirectEaiForTashkent` parametri, ichki flag
emas) — hozir **ikkala chaqiruvchi ham har doim `false` beradi**: `ConfirmStageHandler`
`Yii::$app->params['osago']['enableDirectEaiForTashkent']`ni o'qiydi (`backend/config/params.php`da,
standart `false`), `WebAppController::actionSubmit()` esa endi `false`ni to'g'ridan-to'g'ri beradi (ilgari
har doim `true` bergan, ya'ni Toshkent raqamlarini to'g'ridan-to'g'ri EAI'ga yuborgan — bu endi kerak
emas deb qaror qilindi, EAI muammolari tufayli). Demak amaliyotda barcha polisalar — Toshkent ham —
Gross navbatiga boradi; ikkalasi ham buni bir xil `isTashkentPlate()` tekshiruvi orqali amalga oshiradi.

Root darajadagi `web-app/` papka — bu Mini App'ning Claude Design orqali chizilgan maketi
(`Avtosugurta MiniApp.dc.html` + `support.js`), ishlab turgan kod emas.

### Ikkita sug'urta provayderi

- **EuroAsia (EAI)** — `backend/component/EuroAsiaService.php` + `backend/models/EuroAsia.php` +
  `backend/component/*Extractor.php`/`*DTO.php` juftliklari (GraphQL javoblarini DTO'ga aylantiradi:
  `CalculateOsagoDTO`, `CreateOsagoDTO`, `PersonByPinflDTO`, `PolicyByIdDTO` va h.k.). Har bir DTO'ning
  o'ziga mos `*Extractor` klassi bor — GraphQL javobini shu DTO'ga map qiladi.
- **Gross Insurance** — `backend/gross/GrossOsago.php` + `GrossOsagoClient.php` — login, captcha va
  cookie-sessiya asosida ishlaydigan, brauzer sifatida saytga so'rov yuboruvchi klient (`gross_cookie.txt`,
  `captcha.jpg`, `loginpage.txt` — runtime/debug fayllari, versiyalanmaydi). Bu integratsiya sinxron emas —
  `backend/queue/GrossOsagoJob.php` orqali `grossQueue`ga navbatga qo'yiladi (qayta urinishlar,
  `maxAttempts`/`retryDelay`/`maxRounds` bilan — oxirgi commitlar shu qayta-urinish logikasi ustida
  ishlangan).

### Queue'lar (yii2-queue, DB drayver)

To'rtta alohida DB-navbat kanali bor, har biri `console/config/main.php` va `backend/config/main.php` da
komponent sifatida e'lon qilingan va `bootstrap`da ishga tushiriladi:

- `paynetQueue` → `backend/queue/PaynetQueue.php` — Paynet orqali to'lovni amalga oshiradi.
- `grossQueue` → `backend/queue/GrossOsagoJob.php` — Gross Insurance orqali polisa yaratadi.
- `broadcastQueue` → `backend/queue/BroadcastSendJob.php` / `BroadcastDeleteJob.php` — botdan ommaviy
  xabar yuborish/o'chirish.
- `erspQueue` → `backend/queue/ErspLookupJob.php` — "Mening avtolarim" bo'limidagi "✅ Tekshirish"
  bosilganda ersp.e-osgo.uz'dan (captcha+AI, `backend/ersp/ErspVehicleClient.php`) avtomobilning
  amaldagi sug'urta polisalarini olib, `SavedVehicle` (`common/models/SavedVehicle.php`) jadvaliga
  keshlaydi. Bitta avtomobil uchun ketma-ket tekshiruvlar orasida kamida
  `SavedVehicle::CHECK_COOLDOWN_SECONDS` (10 daqiqa) o'tishi shart — bu ersp.e-osgo.uz'ga ortiqcha
  so'rov yubormaslik uchun; cheklov `SavedVehicle::canCheckNow()`/`secondsUntilNextCheck()` orqali
  bot (`MyVehiclesStageHandler::triggerCheck()`) va Web App (`WebAppController::actionMyVehicleCheck()`)
  ikkalasida ham serverda tekshiriladi (faqat "✅ Tekshirish" tugmasini yashirish yetarli emas).

Navbat workerlari alohida process sifatida ishga tushiriladi (`php yii <kanal>/listen`), veb-so'rov ichida
emas — shuning uchun to'lov/polisa natijasi bot foydalanuvchisiga **keyinroq**, worker ishini tugatgach
yuboriladi (odatda `Yii::$app->telegram->sendMessage()` to'g'ridan-to'g'ri yoki `BotMessenger` orqali).
Productionda `erspQueue` uchun tayyor systemd xizmat fayli bor: `deploy/systemd/ersp-queue.service`
(o'rnatish/boshqarish buyruqlari fayl ichidagi izohlarda) — kodni serverga deploy qilgandan keyin
`systemctl restart ersp-queue` bilan workerni qayta ishga tushirish kerak.

### Paynet to'lov integratsiyasi

`common/models/Paynet.php` — har bir Paynet terminali (login) uchun alohida yozuv, o'zining
`api_token`i bilan (admin panelda generatsiya/rotatsiya qilinadi, `PaynetController::actionToken` orqali —
tokenni yangilash eskisini bekor qiladi). `backend/models/PaynetAPI2.php` — Paynet'ning tashqi API'siga
haqiqiy so'rov yuboruvchi klient. Ichki (backend-uchun-backend) to'lov endpointlari haqida batafsil
ma'lumot `docs/PAYNET_PAY_API.md` da — **diqqat: bu hujjat `Pay5sd4fs5df41Controller` nomli controllerni
tasvirlaydi, lekin joriy kodda bunday fayl yo'q** (faqat admin-CRUD `PaynetController` mavjud) — hujjatni
qo'llashdan oldin controller haqiqatan mavjudligini tekshiring.

### Ma'lumotlar modeli (asosiy jadvallar, `common/models/`)

`User`/`LoginForm` — admin panel foydalanuvchilari (RBAC, `authManager` → `yii\rbac\DbManager`).
`Botuser` — Telegram foydalanuvchisi + `data` JSON holat blobi. `Police` — yaratilgan sug'urta polisasi.
`Payment` — to'lov buyurtmasi (Paynet navbati shu yerga yozadi/o'qiydi). `Owner`/`Driver`/`Vehicle`/
`Relative` — polisa uchun kiritilgan shaxs/avtomobil ma'lumotlari. `SeasonalInsurance` — mavsumiy
sug'urta variantlari (1 yil/6 oy/20 kun — qattiq kodlangan GUID/kun/`period_type` qiymatlari
`backend/component/insurance/SeasonalInsuranceCatalog.php`da, bot va WebApp ikkalasi uchun ham yagona
manba). `Setting` —
kalit-qiymat global sozlamalar (bot/police/payment on-off). `Text` — ko'p tilli bot matnlari. `History` —
foydalanuvchi xabarlari logi. `Broadcast`/`BroadcastLog` — ommaviy xabar yuborish. `Deeplink` — bot uchun
deeplink/referral kuzatuvi.

### Telegram API wrapper

`common/eleirbag/Telegram.php` (~64KB) — Telegram Bot API'ning to'liq qo'lda yozilgan wrapper klassi
(`sendMessage`, `getData`, klaviatura yordamchilari va h.k.), `telegram` ilova komponenti sifatida
`common`, `backend` va `console` konfiguratsiyalarida ro'yxatdan o'tgan (`bot_token` — `.env`dagi
`TELEGRAM_BOT_TOKEN`). `TelegramErrorLogger.php` — shu klass uchun xatoliklarni loglash yordamchisi.

## Muhim eslatmalar

- `backend/gross/` papkasidagi `gross_cookie.txt`, `captcha.jpg`, `loginpage.txt`,
  `backend/gross/responses/` — Gross integratsiyasi debug/runtime artefaktlari, qo'lda tahrirlanmaydi.
- Botga yangi bosqich qo'shganda naqshga rioya qiling: `Pages::` konstantasi → `backend/component/bot/stage/`da
  `BotStageInterface`ni amalga oshiruvchi klass (yoki mavjudiga metod) → `BotController::buildStageRegistry()`ga
  bitta `register()` qatori. Bosqichlar orasidagi o'tish kerak bo'lsa, tegishli stage-handler klassni
  konstruktor orqali in'ektsiya qiling (registry orqali emas — faqat `actionStart()`ning yakuniy dispatchi
  registrydan foydalanadi).
- Bot (`ConfirmStageHandler::handle()`) va Mini App (`WebAppController::actionSubmit()`) ariza yuborish
  bosqichida endi bitta umumiy `OsagoSubmissionService::submit()`ni chaqiradi — EAI/Gross branch qarori,
  `Police` yozish va Gross navbatiga qo'yish mantig'i endi bitta joyda. Har ikkalasi ham o'z ma'lumotlarini
  `OsagoApplicationData`ga moslab (Bot: `OsagoApplicationData::fromBotState($ctx)`, WebApp: to'g'ridan-
  to'g'ri property tayinlash) shu servisga uzatadi. Validatsiya/xato-xabarlar va input-parsing esa hali ham
  har birida o'ziga xos (presentation-ga xos, ataylab birlashtirilmagan).
