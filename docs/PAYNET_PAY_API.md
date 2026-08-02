# Pay5sd4fs5df41Controller — foydalanish qo'llanmasi

Bu qo'llanma `backend/controllers/Pay5sd4fs5df41Controller.php` orqali ochilgan ichki to'lov endpointlaridan (karta orqali to'lov va telefon raqami orqali to'lov) qanday foydalanish kerakligini tushuntiradi: qanday parametr yuborish, javob qanday keladi va xatoliklarni qanday aniqlash kerak.

> Bu controller `backend/models/PaynetAPI.php` orqali Paynet (`flagship.paynet.uz`) tashqi API'siga so'rov yuboradi. Sizning ilovangiz (klient) faqat quyidagi ikkita endpointga murojaat qiladi — Paynet bilan to'g'ridan-to'g'ri ishlash shart emas.

## 1. Umumiy ma'lumot

- **Controller:** `Pay5sd4fs5df41Controller`
- **Route (Yii2 default):**
  - `POST /pay5sd4fs5df41/card` — karta raqami orqali to'lov
  - `POST /pay5sd4fs5df41/phone` — telefon raqami orqali to'lov
  - (Aniq domen/baseUrl loyihangiz serverida qanday sozlanganiga bog'liq, masalan `https://<sizning-domen>/pay5sd4fs5df41/card`.)
- **Method:** `POST`, `Content-Type: application/x-www-form-urlencoded` (yoki `multipart/form-data`) — parametrlar `$_POST` orqali o'qiladi, JSON body emas.
- **CSRF:** o'chirilgan (`enableCsrfValidation = false`), CSRF token yuborish shart emas.
- **Autentifikatsiya:** `secret` maydoni orqali, lekin endi bu **har bir Paynet login (terminal) uchun alohida generatsiya qilingan API token** — pastdagi 1.1-bo'limga qarang. Bu maxfiy qiymat — faqat ishonchli backend-backend chaqiruvlar uchun mo'ljallangan, hech qachon frontendga chiqarilmasin.

### 1.1. API token qanday olinadi va yangilanadi

Avval umumiy, barcha terminallar uchun bitta qattiq yozilgan `"sarmin"` qiymati ishlatilar edi. Endi **har bir Paynet login (terminal)ning o'z alohida tokeni bor**, va shu token noto'g'ri yoki bo'sh bo'lsa so'rov rad etiladi.

- Admin panelda `Paynet` bo'limiga o'ting, kerakli terminalning **view** (ko'rish) sahifasini oching: `/paynet/view?id=<paynetId>`.
- Sahifada **"Token yaratish"** (agar hali token bo'lmasa) yoki **"Token yangilash"** (agar allaqachon mavjud bo'lsa) tugmasi bor.
- Tugma bosilganda `POST /paynet/token?id=<paynetId>` chaqiriladi (`PaynetController::actionToken()`), yangi tasodifiy token generatsiya qilinadi, bazaga (`paynet.api_token`) saqlanadi va sahifada flash-xabar sifatida ko'rsatiladi.
- **Muhim:** har safar bu tugma bosilganda **eski token bekor bo'ladi** — endi faqat eng oxirgi generatsiya qilingan token ishlaydi. Shuning uchun tokenni yangilashdan oldin uni ishlatayotgan klient tomonni ham yangi token bilan yangilash kerak, aks holda o'sha klientning so'rovlari `"Noto'g'ri token!"` xatoligi bilan rad etila boshlaydi.
- Token bazada `paynet.api_token` ustunida saqlanadi (`DetailView`da "Api Token" sifatida ko'rinadi) — shu yerdan nusxalab olib, klient ilovaga kiritish mumkin.
- Terminal uchun hali token generatsiya qilinmagan bo'lsa (`api_token` bo'sh), `/pay5sd4fs5df41/*` endpointlariga qilingan har qanday so'rov `"Noto'g'ri token!"` xatoligini qaytaradi — avval albatta token yarating.

## 2. `POST /pay5sd4fs5df41/card` — karta orqali to'lov

Ichki oqim: avval karta ma'lumotlari (`payInfo`) tekshiriladi, so'ng shu ma'lumot asosida to'lov (`payCard`) amalga oshiriladi.

### So'rov parametrlari

| Parametr | Majburiy | Tavsif |
|---|---|---|
| `secret` | ha | Shu `paynetId`ga tegishli terminalning API tokeni (1.1-bo'limga qarang). Noto'g'ri yoki bo'sh bo'lsa `"Noto'g'ri token!"` qaytadi |
| `amount` | ha | To'lov summasi (so'm). Satr yoki son sifatida yuborish mumkin |
| `cardNumber` | ha | Qabul qiluvchi plastik karta raqami (16 xonali, masalan `9860120152301111`) |
| `paynetId` | ha | `paynet` jadvalidagi terminalning `id`si — qaysi Paynet terminal orqali to'lov qilinishini bildiradi |

**Misol (cURL):**
```bash
curl -X POST "https://<sizning-domen>/pay5sd4fs5df41/card" \
  -d "secret=<terminalning-api_token-qiymati>" \
  -d "amount=100000" \
  -d "cardNumber=9860120152301111" \
  -d "paynetId=1"
```

### Javob formati

Har doim JSON, quyidagi umumiy tuzilma bilan:

```json
{
  "status": true,
  "message": "...",
  "paynet_id": 1,
  "data": {}
}
```
(`data` ichida PaynetAPI'dan kelgan xom natija bo'ladi — quyidagi har bir holat uchun aniq misollarga qarang.)

| Maydon | Tavsif |
|---|---|
| `status` | `true` — to'lov muvaffaqiyatli o'tdi. `false` — xatolik yoki muvaffaqiyatsizlik |
| `message` | Foydalanuvchiga ko'rsatsa bo'ladigan matn. Paynet har bir javobida (ham muvaffaqiyat, ham xatolikda) `statusText` degan maydon orqali inson o'qiy oladigan xabar qaytaradi (masalan `"Field required!"`, `"humo card info error"`, `"Insufficient funds"` va h.k.) — shu matn to'g'ridan-to'g'ri shu yerga o'tkaziladi. Agar Paynet biror sababdan `statusText` qaytarmagan bo'lsa, umumiy fallback xabar (masalan `"To'lov amalga oshmadi!"`, `"To'lov o'tkazildi!"`) ishlatiladi |
| `paynet_id` | So'rovda yuborilgan `paynetId` bilan bir xil, javobni qaysi terminalga tegishli ekanini aniqlash uchun |
| `data` | Ixtiyoriy — mavjud bo'lsa, PaynetAPI'dan kelgan xom natija (quyida batafsil) |

### Muvaffaqiyatli to'lov

```json
{
  "status": true,
  "message": "To'lov o'tkazildi!",
  "paynet_id": 1,
  "data": { "status": false, "message": null }
}
```
(`data.status: false` shu yerda Paynet tomonidan "xatolik yo'q" degani — chalkash tuyulsa ham, bu Paynet API'ning o'z konvensiyasi.) Agar Paynet muvaffaqiyatli javobda ham o'z `statusText`ini qaytarsa, top-level `message` xuddi shu matnni ko'rsatadi; aks holda `"To'lov o'tkazildi!"` fallback sifatida qoladi.

### Xatolik holatlari

**1) Parametr yetishmasa:**
```json
{ "status": false, "message": "Missing parameters", "paynet_id": null }
```

**2) API token noto'g'ri, bo'sh yoki mos kelmasa:**
```json
{ "status": false, "message": "Noto'g'ri token!", "paynet_id": null }
```
`secret`da yuborilgan qiymat shu `paynetId`ning joriy `api_token`iga mos kelmasa (yoki terminal uchun umuman token generatsiya qilinmagan bo'lsa) shu xatolik qaytadi. Yechim: 1.1-bo'limdagi kabi admin panelda terminal uchun token yarating/yangilang va klient tomonda shu qiymatni ishlating.

**3) Paynet'dagi ichki sessiya yangilanmasa (`refresh()` muvaffaqiyatsiz):**
```json
{ "status": false, "message": "Refresh foyda bermadi!", "paynet_id": 1 }
```
Bu — Paynet'ning o'zidagi login sessiyasi bilan bog'liq muammo, yuqoridagi API tokenga aloqasi yo'q. Bu holatda Paynet akkaunt qayta login/OTP talab qilishi mumkin — administratsiya tomonidan `paynet` yozuvining login holatini tekshirish kerak.

**4) Karta ma'lumotini olishda xatolik (`payInfo` bosqichi):**
```json
{
  "status": false,
  "message": "Field required!",
  "paynet_id": 1,
  "data": {
    "status": 1010,
    "UID": "...",
    "recipient_name": "...",
    "card_type": "...",
    "amount": "..."
  }
}
```
`message` — Paynet serveridan kelgan haqiqiy sabab. Bu bosqichda karta noto'g'ri, bloklangan yoki boshqa sabab bilan rad etilgan bo'lishi mumkin.

**5) To'lov bosqichida xatolik (`payCard`):**
```json
{
  "status": false,
  "message": "<Paynet statusText matni yoki 'To'lov amalga oshmadi!'>",
  "paynet_id": 1,
  "data": { "status": 1010, "message": "..." }
}
```

**6) Sessiya tugagan (401):** Ichki tarzda avtomatik `refresh()` orqali qayta urinish qilinadi; agar u ham muvaffaqiyatsiz bo'lsa, natija yuqoridagi "Refresh foyda bermadi!" yoki umumiy xabar bilan qaytadi. Klient tomonda alohida `401` kodini ushlash shart emas — barcha holatlarda javob shu JSON formatida keladi (HTTP status har doim 200).

**7) Kutilmagan server xatoligi:**
```json
{ "status": false, "message": "Serverda xatolik: <PHP exception matni>", "paynet_id": 1 }
```

## 3. `POST /pay5sd4fs5df41/phone` — telefon raqami orqali to'lov

### So'rov parametrlari

| Parametr | Majburiy | Tavsif |
|---|---|---|
| `secret` | ha | Shu `paynetId`ga tegishli terminalning API tokeni (1.1-bo'limga qarang) |
| `amount` | ha | To'lov summasi |
| `phoneNumber` | ha | Qabul qiluvchi telefon raqami |
| `paynetId` | ha | `paynet` jadvalidagi terminal `id`si |

**Misol (cURL):**
```bash
curl -X POST "https://<sizning-domen>/pay5sd4fs5df41/phone" \
  -d "secret=<terminalning-api_token-qiymati>" \
  -d "amount=50000" \
  -d "phoneNumber=998901234567" \
  -d "paynetId=1"
```

### Javob formati

Xuddi `/card` bilan bir xil tuzilma (`status`, `message`, `paynet_id`, `data`):

- **Muvaffaqiyat:** `{"status": true, "message": "To'lov o'tkazildi!", "paynet_id": 1, "data": {"status": false}}`
- **Xatolik:** `{"status": false, "message": "<Paynet statusText yoki 'To'lov amalga oshmadi!'>", "paynet_id": 1, "data": {"status": ..., "message": "..."}}`
- **Parametr yetishmasa / noto'g'ri token / ichki sessiya muammosi / server xatoligi:** yuqoridagi `/card` bo'limidagi 1, 2, 3, 7-holatlar bilan bir xil.

`/phone` bitta bosqichli (`pay()`), `payInfo`/`payCard` kabi oldindan tekshirish bosqichi yo'q.

## 4. Xatoliklarni qanday ishlatish kerak (klient tomon uchun tavsiya)

1. Har doim avval top-level `status`ni tekshiring: `true` — to'lov muvaffaqiyatli, `false` — muvaffaqiyatsiz/xatolik.
2. Foydalanuvchiga ko'rsatiladigan xabar uchun **top-level `message`** maydonini ishlating — u allaqachon inson o'qiy oladigan shaklda (Paynet o'zining `statusText` maydonidan olingan real xabar, muvaffaqiyat yoki xatolikdan qat'i nazar; agar Paynet uni bermasa — umumiy fallback matn). Paynet'ning xom javobida bu xabar `statusText` deb nomlanadi — o'zingiz Paynet bilan to'g'ridan-to'g'ri ishlaganda ham shu maydonga qarang, `message` degan maydon Paynet javobida mavjud emas.
3. Agar tafsilot yoki debugging kerak bo'lsa (masalan qaysi Paynet status kodi qaytgani), `data` maydonini tekshiring — lekin foydalanuvchiga uni to'g'ridan-to'g'ri ko'rsatmang, chunki u texnik/xom ma'lumot.
4. HTTP status kodiga tayanmang — bu endpointlar har doim `200 OK` bilan javob beradi, muvaffaqiyat/xatolik faqat JSON body ichidagi `status` maydoni orqali aniqlanadi.
5. `paynet_id` javobdagi qiymat so'rovda yuborgan `paynetId` bilan bir xil bo'lishi kerak — agar bir nechta terminal bilan parallel ishlayotgan bo'lsangiz, javobni so'rov bilan moslashtirish uchun shundan foydalaning.

## 5. Eslatmalar

- Har bir so'rov davomida terminal "band" (`is_payment_processing`) deb belgilanadi va boshqa parallel so'rov shu terminal bo'yicha navbatga tushmaydi — token yangilanayotgan bo'lsa (`is_token_updating`), so'rov ichida avtomatik kutish (`sleep`) bo'ladi. Shu sabab bitta terminal orqali javob bir necha soniya kechikishi mumkin (`set_time_limit(600)` — 10 daqiqagacha).
- `commission_percent` (komissiya foizi) `payInfo` so'rovida serverda qattiq `"3"` qiymati bilan yuboriladi — bu klient tomonidan o'zgartirilmaydi.
- API token (`secret`) har bir terminal uchun alohida va faqat bitta amaldagi qiymatga ega — "Token yangilash" bosilganda eskisi darhol ishlamay qoladi. Token oshkor bo'lib qolgan (leak) deb gumon qilinsa, uni darhol admin panel orqali yangilang.