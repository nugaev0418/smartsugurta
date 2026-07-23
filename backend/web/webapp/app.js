(function () {
  'use strict';

  // -----------------------------------------------------------------------
  // i18n
  // -----------------------------------------------------------------------
  // All user-facing text lives here, keyed the same way as the data-i18n /
  // data-i18n-placeholder / data-i18n-aria attributes in index.html for the
  // static markup, plus extra keys used only from JS-generated strings.
  var STRINGS = {
    successTitle: { uz: "Ariza qabul qilindi", ru: "Заявка принята" },
    paymentLinkBtn: { uz: "To'lovni amalga oshirish", ru: "Перейти к оплате" },
    closeBtn: { uz: "✕ Yopish", ru: "✕ Закрыть" },
    step1Title: { uz: "Transport ma'lumotlari", ru: "Данные транспорта" },
    step1Sub: { uz: "Avtomobilingiz haqidagi ma'lumotlarni kiriting", ru: "Введите данные о вашем автомобиле" },
    labelPlate: { uz: "Davlat raqami", ru: "Госномер" },
    labelTech: { uz: "Texpassport seriya va raqami", ru: "Серия и номер техпаспорта" },
    checkingText: { uz: "Tekshirilmoqda...", ru: "Проверка..." },
    step2Title: { uz: "Avtomobil egasi", ru: "Владелец автомобиля" },
    step2Sub: { uz: "Avtomobil egasi ma'lumotlarini kiriting", ru: "Введите данные владельца автомобиля" },
    labelPassport: { uz: "Passport seriya va raqami", ru: "Серия и номер паспорта" },
    labelOrgName: { uz: "Tashkilot nomi", ru: "Название организации" },
    step3Title: { uz: "Sug'urta turi", ru: "Тип страхования" },
    step3Sub: { uz: "Sizga mos variantni tanlang", ru: "Выберите подходящий вариант" },
    labelInsurerPhone: { uz: "Sug'urtalovchi telefon raqami", ru: "Номер телефона страхователя" },
    step4Title: { uz: "Haydovchilar", ru: "Водители" },
    step4Sub: { uz: "Ro'yxatga haydovchilarni qo'shing", ru: "Добавьте водителей в список" },
    ownerIsDriverToggle: { uz: "Avtomobil egasi ham haydovchimi?", ru: "Владелец авто тоже за рулём?" },
    addDriverBtn: { uz: "+ Haydovchi qo'shish", ru: "+ Добавить водителя" },
    step5Title: { uz: "Sug'urta muddati", ru: "Срок страхования" },
    step5Sub: { uz: "Boshlanish sanasi va muddatni tanlang", ru: "Выберите дату начала и срок" },
    labelStartDate: { uz: "Boshlanish sanasi", ru: "Дата начала" },
    labelDuration: { uz: "Muddat", ru: "Срок" },
    step6Title: { uz: "Ma'lumotlarni ko'rib chiqing", ru: "Проверьте данные" },
    step6Sub: { uz: "Yuborishdan oldin tekshirib chiqing", ru: "Проверьте перед отправкой" },
    sumHeadApplicant: { uz: "Arizaberuvchi", ru: "Заявитель" },
    editLink: { uz: "tahrirlash", ru: "изменить" },
    sumHeadTransport: { uz: "Transport ma'lumotlari", ru: "Данные транспорта" },
    sumHeadOwner: { uz: "Avtomobil egasi", ru: "Владелец автомобиля" },
    sumHeadDrivers: { uz: "Haydovchilar", ru: "Водители" },
    sumHeadDuration: { uz: "Sug'urta muddati", ru: "Срок страхования" },
    labelGateway: { uz: "To'lov usuli", ru: "Способ оплаты" },
    labelPrice: { uz: "Sug'urta narxi", ru: "Стоимость страховки" },
    backBtn: { uz: "Orqaga", ru: "Назад" },
    nextBtn: { uz: "Davom etish", ru: "Продолжить" },
    nextBtnFinal: { uz: "To'lovga o'tish", ru: "Перейти к оплате" },
    dateMask: { uz: "KK.OO.YYYY", ru: "ДД.ММ.ГГГГ" },
    ariaDatePick: { uz: "Sanani tanlash", ru: "Выбрать дату" },

    stepLabelPrefix: { uz: "Qadam", ru: "Шаг" },
    ownerTypeOrg: { uz: "Yuridik shaxs", ru: "Юридическое лицо" },
    ownerTypePerson: { uz: "Jismoniy shaxs", ru: "Физическое лицо" },
    modelPrefix: { uz: "Model:", ru: "Модель:" },
    typePrefix: { uz: "Turi:", ru: "Тип:" },
    insuranceLimitedTitle: { uz: "Cheklangan", ru: "Ограниченный" },
    insuranceLimitedDesc: {
      uz: "Faqat ro'yxatdagi haydovchilar transport vositasini boshqarishi mumkin",
      ru: "Управлять транспортом могут только водители из списка",
    },
    insuranceUnlimitedTitle: { uz: "Cheklanmagan", ru: "Неограниченный" },
    insuranceUnlimitedDesc: {
      uz: "Har qanday shaxs transport vositasini boshqarishi mumkin",
      ru: "Управлять транспортом может любое лицо",
    },
    driversCountSuffix: { uz: "haydovchi", ru: "водителей" },
    driverSelfLabel: { uz: "Siz (avtomobil egasi)", ru: "Вы (владелец авто)" },
    driverLabelPrefix: { uz: "Haydovchi", ru: "Водитель" },
    placeholderSeria: { uz: "Seriya", ru: "Серия" },
    placeholderNumber: { uz: "Raqami", ru: "Номер" },
    statusErrorDefault: { uz: "Xato", ru: "Ошибка" },
    plateSummaryPrefix: { uz: "Davlat raqami:", ru: "Госномер:" },
    techSummaryPrefix: { uz: "Texpassport:", ru: "Техпаспорт:" },
    priceCurrency: { uz: "so'm", ru: "сум" },

    toastVehicleNotFound: { uz: "Transport topilmadi", ru: "Транспорт не найден" },
    toastGenericError: { uz: "Xatolik yuz berdi", ru: "Произошла ошибка" },
    toastOwnerNotFound: { uz: "Egasi topilmadi", ru: "Владелец не найден" },
    toastMaxDrivers: { uz: "Ko'pi bilan 5 ta haydovchi qo'shish mumkin", ru: "Можно добавить не более 5 водителей" },
    toastSubmitError: { uz: "Xatolik yuz berdi. Qayta urinib ko'ring", ru: "Произошла ошибка. Попробуйте снова" },
    successDefaultMessage: { uz: "So'rovingiz qabul qilindi.", ru: "Ваша заявка принята." },

    errPlateIncomplete: { uz: "Davlat raqamini to'liq kiriting", ru: "Введите госномер полностью" },
    errTechIncomplete: {
      uz: "Seriya (3 harf) va raqam (7 raqam) to'liq kiritilishi kerak",
      ru: "Серия (3 буквы) и номер (7 цифр) должны быть введены полностью",
    },
    errPassportIncomplete: { uz: "Passport ma'lumotlarini to'liq kiriting", ru: "Введите данные паспорта полностью" },
    errInsuranceType: { uz: "Sug'urta turini tanlang", ru: "Выберите тип страхования" },
    errPhoneIncomplete: { uz: "Telefon raqamini to'liq kiriting", ru: "Введите номер телефона полностью" },
    errStartDate: { uz: "Boshlanish sanasini tanlang", ru: "Выберите дату начала" },
    errDuration: { uz: "Muddatni tanlang", ru: "Выберите срок" },
    errAtLeastOneDriver: { uz: "Kamida bitta haydovchi qo'shing", ru: "Добавьте хотя бы одного водителя" },
    errDriversIncomplete: {
      uz: "Barcha haydovchilar ma'lumotini to'liq va to'g'ri kiriting",
      ru: "Введите данные всех водителей полностью и правильно",
    },
  };

  // Display labels for RELATIONS/gateway values that are also submitted to the
  // backend — the *value* (map key) always stays the Uzbek canonical string
  // (WebAppController::RELATIVE_TYPES is keyed on it), only the shown label
  // is translated.
  var RELATION_LABELS = {
    "Qarindosh emas": { uz: "Qarindosh emas", ru: "Не родственник" },
    "Ota": { uz: "Ota", ru: "Отец" },
    "Ona": { uz: "Ona", ru: "Мать" },
    "Er": { uz: "Er", ru: "Муж" },
    "Xotin": { uz: "Xotin", ru: "Жена" },
    "O'g'li": { uz: "O'g'li", ru: "Сын" },
    "Qizi": { uz: "Qizi", ru: "Дочь" },
    "Aka": { uz: "Aka", ru: "Старший брат" },
    "Uka": { uz: "Uka", ru: "Младший брат" },
    "Opa": { uz: "Opa", ru: "Старшая сестра" },
    "Singil": { uz: "Singil", ru: "Младшая сестра" },
    "O'zi": { uz: "O'zi", ru: "Я" },
  };

  var DURATION_LABELS = {
    '20d': { uz: '20 kun', ru: '20 дней' },
    '6m': { uz: '6 oy', ru: '6 месяцев' },
    '1y': { uz: '1 yil', ru: '1 год' },
  };

  var RELATIONS = ["Qarindosh emas", 'Aka', 'Uka', 'Ota', 'Ona', 'Opa', 'Singil', 'Xotin', 'Er', "O'g'li", 'Qizi'];
  var DURATIONS = [
    { key: '20d', days: 20 },
    { key: '6m', days: 180 },
    { key: '1y', days: 365 },
  ];

  var state = {
    lang: 'uz',
    step: 1,
    plateNumber: '', techSeria: '', techNumber: '',
    checking: false,
    vehicleData: null,
    ownerData: null,
    physSeries: '', physNumber: '',
    insuranceType: null,
    phone: '',
    ownerIsDriver: false,
    drivers: [],
    startDate: todayYmd(),
    duration: null,
    premium: null,
    gateway: 'CLICK',
    submitting: false,
  };

  function t(key) {
    var entry = STRINGS[key];
    if (!entry) return key;
    return entry[state.lang] || entry.uz;
  }

  function relationLabel(value) {
    var entry = RELATION_LABELS[value];
    return entry ? (entry[state.lang] || entry.uz) : value;
  }

  function durationLabel(key) {
    var entry = DURATION_LABELS[key];
    return entry ? (entry[state.lang] || entry.uz) : key;
  }

  function applyStaticI18n() {
    var nodes = document.querySelectorAll('[data-i18n]');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = t(nodes[i].getAttribute('data-i18n'));
    }
    var placeholderNodes = document.querySelectorAll('[data-i18n-placeholder]');
    for (var j = 0; j < placeholderNodes.length; j++) {
      placeholderNodes[j].setAttribute('placeholder', t(placeholderNodes[j].getAttribute('data-i18n-placeholder')));
    }
    var ariaNodes = document.querySelectorAll('[data-i18n-aria]');
    for (var k = 0; k < ariaNodes.length; k++) {
      ariaNodes[k].setAttribute('aria-label', t(ariaNodes[k].getAttribute('data-i18n-aria')));
    }
  }

  function $(id) { return document.getElementById(id); }

  function pad2(n) { return (n < 10 ? '0' : '') + n; }

  // Local-calendar-date helpers. Deliberately avoid Date#toISOString() here:
  // it converts to UTC, which silently shifts the date back a day for any
  // timezone ahead of UTC (e.g. Asia/Tashkent, UTC+5) whenever the underlying
  // Date ends up representing local midnight — exactly the "1 kun kam" bug.
  function todayYmd() {
    var d = new Date();
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function fmtDate(ymd) {
    if (!ymd) return '';
    var p = ymd.split('-');
    return p[2] + '.' + p[1] + '.' + p[0];
  }

  function formatDateMask(raw) {
    var d = raw.replace(/\D/g, '').slice(0, 8);
    var out = d.slice(0, 2);
    if (d.length > 2) out += '.' + d.slice(2, 4);
    if (d.length > 4) out += '.' + d.slice(4, 8);
    return out;
  }

  function maskToYmd(masked) {
    var m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(masked);
    if (!m) return '';
    var day = +m[1], month = +m[2], year = +m[3];
    if (month < 1 || month > 12) return '';
    if (day < 1 || day > new Date(year, month, 0).getDate()) return '';
    return year + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
  }

  function wireDateField(textEl, nativeEl, pickBtn, onCommit) {
    textEl.addEventListener('input', function (e) {
      var masked = formatDateMask(e.target.value);
      e.target.value = masked;
      if (masked.length === 10) {
        var ymd = maskToYmd(masked);
        textEl.classList.toggle('invalid', !ymd);
        if (ymd) { nativeEl.value = ymd; onCommit(ymd); }
      } else {
        textEl.classList.remove('invalid');
      }
    });
    nativeEl.addEventListener('change', function (e) {
      textEl.value = fmtDate(e.target.value);
      textEl.classList.remove('invalid');
      onCommit(e.target.value);
    });
    pickBtn.addEventListener('click', function () {
      if (typeof nativeEl.showPicker === 'function') {
        try { nativeEl.showPicker(); return; } catch (err) { /* fall through to focus/click */ }
      }
      nativeEl.focus();
      nativeEl.click();
    });
  }

  function durationDays(key) {
    var d = DURATIONS.filter(function (o) { return o.key === key; })[0];
    return d ? d.days : 0;
  }

  function addDaysYmd(ymd, days) {
    var p = ymd.split('-').map(Number);
    var d = new Date(p[0], p[1] - 1, p[2]);
    d.setDate(d.getDate() + days);
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function isoToYmd(iso) {
    if (!iso) return '';
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso));
    return m ? m[0] : '';
  }

  var toastTimer = null;
  function showToast(msg) {
    var el = $('toast');
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.classList.add('hidden'); }, 2800);
  }

  function setErr(field, msg) {
    var el = $('err-' + field);
    if (el) el.textContent = msg || '';
  }

  function markInvalid(id, invalid) {
    var el = $(id);
    if (!el) return;
    el.classList.toggle('invalid', !!invalid);
  }

  // Jumps focus to the paired "number" field right when "seria" just became
  // full via typing — not while merely editing an already-full field (no-op
  // unless wasFull is false), and not on a delete keystroke.
  function autoAdvance(e, wasFull, nowFull, nextEl) {
    if (!nextEl || wasFull || !nowFull) return;
    var t = e.inputType;
    if (t && /^delete/.test(t)) return;
    nextEl.focus();
  }

  function telegramInitData() {
    var tg = window.Telegram && window.Telegram.WebApp;
    return tg ? tg.initData : '';
  }

  // Global request counter so overlapping api() calls (e.g. a driver check
  // still in flight when "calculate" fires) keep the loader up until the
  // last one finishes, instead of each call independently show/hiding it.
  var pendingRequests = 0;
  function setGlobalLoading(loading) {
    var el = $('globalLoader');
    if (el) el.classList.toggle('hidden', !loading);
  }

  function api(action, body) {
    // initData is always attached here (not left to each call site) so every
    // endpoint can be verified server-side as coming from the bot's admin.
    var payload = Object.assign({}, body || {}, { initData: telegramInitData() });
    pendingRequests++;
    setGlobalLoading(true);
    return fetch('/web-app/' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).then(function (r) { return r.json(); })
      .finally(function () {
        pendingRequests = Math.max(0, pendingRequests - 1);
        if (pendingRequests === 0) setGlobalLoading(false);
      });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // ---------------------------------------------------------------------
  // Rendering
  // ---------------------------------------------------------------------

  function render() {
    var steps = document.querySelectorAll('.step');
    for (var i = 0; i < steps.length; i++) {
      steps[i].classList.toggle('active', parseInt(steps[i].dataset.step, 10) === state.step);
    }

    $('stepLabel').textContent = t('stepLabelPrefix') + ' ' + state.step + '/6';
    $('stepTitle').textContent = stepTitle(state.step);
    $('progressBar').style.width = (state.step / 6 * 100) + '%';

    $('backBtn').classList.toggle('hidden', state.step === 1);
    $('nextBtn').textContent = state.step === 6 ? t('nextBtnFinal') : t('nextBtn');
    $('nextBtn').disabled = state.checking || state.submitting;

    $('checkingIndicator').classList.toggle('hidden', !(state.step === 1 && state.checking));

    if (state.step === 2) renderStep2();
    if (state.step === 3) renderStep3();
    if (state.step === 4) renderStep4();
    if (state.step === 5) renderStep5();
    if (state.step === 6) renderStep6();
  }

  function stepTitle(step) {
    return {
      1: t('step1Title'), 2: t('step2Title'), 3: t('step3Title'),
      4: t('step4Title'), 5: t('step5Title'), 6: t('step6Title'),
    }[step];
  }

  function goToStep(n) { state.step = n; render(); }

  function ownerTypeLabel(vt) {
    return vt === 'ORGANIZATION' ? t('ownerTypeOrg') : t('ownerTypePerson');
  }

  function renderStep2() {
    var vt = state.vehicleData.ownerType;
    $('ownerTypeLabel').textContent = ownerTypeLabel(vt);
    $('physFields').classList.toggle('hidden', vt !== 'PERSON');
    $('legalFields').classList.toggle('hidden', vt === 'PERSON');
    if (vt === 'ORGANIZATION') {
      $('orgNameView').value = state.vehicleData.name || '';
    } else {
      $('physSeries').value = state.physSeries;
      $('physNumber').value = state.physNumber;
    }

    var hasVehicleInfo = !!(state.vehicleData.model || state.vehicleData.vehicleTypeName);
    $('vehicleInfoCard').classList.toggle('hidden', !hasVehicleInfo);
    $('vehicleModelLine').innerHTML = state.vehicleData.model
      ? t('modelPrefix') + ' <strong>' + escapeHtml(state.vehicleData.model) + '</strong>' : '';
    $('vehicleTypeLine').innerHTML = state.vehicleData.vehicleTypeName
      ? t('typePrefix') + ' <strong>' + escapeHtml(state.vehicleData.vehicleTypeName) + '</strong>' : '';
  }

  function renderStep3() {
    var container = $('insuranceCards');
    container.innerHTML = '';
    var cards = [
      { key: 'limited', title: t('insuranceLimitedTitle'), desc: t('insuranceLimitedDesc') },
      { key: 'unlimited', title: t('insuranceUnlimitedTitle'), desc: t('insuranceUnlimitedDesc') },
    ];
    cards.forEach(function (c) {
      var el = document.createElement('div');
      el.className = 'ins-card' + (state.insuranceType === c.key ? ' selected' : '');
      el.innerHTML = '<div class="ins-top"><div class="ins-title">' + escapeHtml(c.title) + '</div><div class="ins-dot"></div></div>' +
        '<div class="ins-desc">' + escapeHtml(c.desc) + '</div>';
      el.addEventListener('click', function () {
        state.insuranceType = c.key;
        setErr('insurance', '');
        renderStep3();
      });
      container.appendChild(el);
    });
    $('phoneInput').value = state.phone;
  }

  function statusClass(status) {
    if (status === 'ok') return 'ok';
    if (status === 'err') return 'err';
    return 'pending';
  }
  function statusText(d) {
    if (d.status === 'ok') return '✓ ' + d.name;
    if (d.status === 'err') return d.statusMsg || t('statusErrorDefault');
    if (d.status === 'checking') return t('checkingText');
    return '';
  }

  function renderStep4() {
    $('driversCountLabel').textContent = state.drivers.length + '/5 ' + t('driversCountSuffix');
    $('addDriverBtn').classList.toggle('hidden', state.drivers.length >= 5);
    var toggle = $('ownerIsDriverToggle');
    toggle.classList.toggle('hidden', !state.vehicleData || state.vehicleData.ownerType !== 'PERSON');
    $('ownerCheckbox').classList.toggle('checked', state.ownerIsDriver);
    renderDriversList();
  }

  function renderDriversList() {
    var container = $('driversList');
    container.innerHTML = '';
    state.drivers.forEach(function (d, i) {
      var card = document.createElement('div');
      card.className = 'driver-card';
      var label = d.isOwner ? t('driverSelfLabel') : t('driverLabelPrefix') + ' ' + (i + 1);
      var safeId = escapeHtml(d.id);
      card.innerHTML =
        '<div class="driver-head"><div class="name">' + escapeHtml(label) + '</div>' +
        (!d.isOwner ? '<div class="driver-remove" data-remove="' + safeId + '">✕</div>' : '') +
        '</div>' +
        '<div class="driver-row">' +
        '<input type="text" placeholder="' + escapeHtml(t('placeholderSeria')) + '" maxlength="2" data-field="seria" data-id="' + safeId + '" value="' + escapeHtml(d.seria) + '">' +
        '<input type="text" placeholder="' + escapeHtml(t('placeholderNumber')) + '" maxlength="7" data-field="number" data-id="' + safeId + '" value="' + escapeHtml(d.number) + '">' +
        '</div>' +
        '<div class="date-field">' +
        '<input type="text" inputmode="numeric" placeholder="' + escapeHtml(t('dateMask')) + '" maxlength="10" data-birth-text value="' + escapeHtml(fmtDate(d.birthDate)) + '">' +
        '<button type="button" class="date-pick-btn" data-birth-pick aria-label="' + escapeHtml(t('ariaDatePick')) + '">📅</button>' +
        '<input type="date" class="date-native" data-birth-native value="' + escapeHtml(d.birthDate) + '" max="' + todayYmd() + '">' +
        '</div>' +
        '<select data-field="relation" data-id="' + safeId + '">' +
        (d.isOwner ? '<option value="O\'zi"' + (d.relation === "O'zi" ? ' selected' : '') + '>' + escapeHtml(relationLabel("O'zi")) + '</option>' : '') +
        RELATIONS.map(function (r) { return '<option value="' + escapeHtml(r) + '"' + (r === d.relation ? ' selected' : '') + '>' + escapeHtml(relationLabel(r)) + '</option>'; }).join('') +
        '</select>' +
        '<div class="driver-status ' + statusClass(d.status) + '">' + escapeHtml(statusText(d)) + '</div>';
      container.appendChild(card);

      wireDateField(
        card.querySelector('[data-birth-text]'),
        card.querySelector('[data-birth-native]'),
        card.querySelector('[data-birth-pick]'),
        function (ymd) {
          d.birthDate = ymd;
          d.status = 'idle'; d.statusMsg = ''; d.name = '';
          updateDriverStatusDom(d);
          maybeValidateDriver(d);
        }
      );
    });

    var fields = container.querySelectorAll('[data-field]');
    for (var i = 0; i < fields.length; i++) {
      var el = fields[i];
      var evt = (el.tagName === 'SELECT' || el.type === 'date') ? 'change' : 'input';
      el.addEventListener(evt, onDriverFieldChange);
    }
    var removes = container.querySelectorAll('[data-remove]');
    for (var j = 0; j < removes.length; j++) {
      removes[j].addEventListener('click', function (e) { removeDriver(e.currentTarget.dataset.remove); });
    }
  }

  function findDriver(id) {
    for (var i = 0; i < state.drivers.length; i++) {
      if (state.drivers[i].id === id) return state.drivers[i];
    }
    return null;
  }

  function onDriverFieldChange(e) {
    var id = e.target.dataset.id;
    var field = e.target.dataset.field;
    var driver = findDriver(id);
    if (!driver) return;

    var oldValue = driver[field];

    var value = e.target.value;
    if (field === 'seria') value = value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
    if (field === 'number') value = value.replace(/[^0-9]/g, '').slice(0, 7);
    driver[field] = value;
    e.target.value = value;

    // Relation doesn't affect passport lookup — changing it must not reset the
    // already-fetched status/name or re-trigger the "driver" API check.
    if (field === 'relation') return;

    if (field === 'seria') {
      var numberEl = e.target.closest('.driver-card').querySelector('[data-field="number"]');
      autoAdvance(e, (oldValue || '').length >= 2, value.length >= 2, numberEl);
    }

    driver.status = 'idle';
    driver.statusMsg = '';
    driver.name = '';
    updateDriverStatusDom(driver);
    maybeValidateDriver(driver);
  }

  function updateDriverStatusDom(driver) {
    var idx = state.drivers.indexOf(driver);
    var cards = document.querySelectorAll('#driversList .driver-card');
    var card = cards[idx];
    if (!card) return;
    var statusEl = card.querySelector('.driver-status');
    statusEl.className = 'driver-status ' + statusClass(driver.status);
    statusEl.textContent = statusText(driver);
  }

  function maybeValidateDriver(driver) {
    if (driver.seria.length === 2 && driver.number.length === 7 && driver.birthDate) {
      driver.status = 'checking';
      updateDriverStatusDom(driver);
      api('driver', { seria: driver.seria, number: driver.number, birthDate: driver.birthDate })
        .then(function (res) {
          if (res.success) {
            driver.status = 'ok';
            driver.name = [res.firstName, res.lastName].filter(Boolean).join(' ');
          } else {
            driver.status = 'err';
            driver.statusMsg = res.message || t('statusErrorDefault');
          }
          updateDriverStatusDom(driver);
        })
        .catch(function () {
          driver.status = 'err';
          driver.statusMsg = t('toastGenericError');
          updateDriverStatusDom(driver);
        });
    }
  }

  function removeDriver(id) {
    state.drivers = state.drivers.filter(function (d) { return d.id !== id; });
    renderStep4();
  }

  function renderStep5() {
    $('startDateInput').value = state.startDate;
    $('startDateInput').min = todayYmd();
    $('startDateText').value = fmtDate(state.startDate);
    var chips = $('durationChips');
    chips.innerHTML = '';
    DURATIONS.forEach(function (o) {
      var el = document.createElement('div');
      el.className = 'chip' + (state.duration === o.key ? ' selected' : '');
      el.textContent = durationLabel(o.key);
      el.addEventListener('click', function () {
        state.duration = o.key;
        setErr('duration', '');
        renderStep5();
      });
      chips.appendChild(el);
    });
  }

  function recomputePremium() {
    return api('calculate', {
      duration: state.duration,
      driverRestriction: state.insuranceType === 'limited',
      useTerritoryRegionId: state.vehicleData.useTerritoryRegionId,
      vehicleGroupId: state.vehicleData.vehicleGroupId,
    }).then(function (res) {
      state.premium = res.success ? res.premium : null;
    }).catch(function () { state.premium = null; });
  }

  function renderStep6() {
    $('sumPhone').textContent = '+998 ' + state.phone;
    $('sumPlate').innerHTML = t('plateSummaryPrefix') + ' <strong>' + escapeHtml(state.plateNumber) + '</strong>';
    $('sumTech').innerHTML = t('techSummaryPrefix') + ' <strong>' + escapeHtml(state.techSeria + state.techNumber) + '</strong>';
    var modelBits = [];
    if (state.vehicleData.model) modelBits.push(escapeHtml(state.vehicleData.model));
    if (state.vehicleData.vehicleTypeName) modelBits.push(escapeHtml(state.vehicleData.vehicleTypeName));
    $('sumVehicleModel').innerHTML = modelBits.length ? t('modelPrefix') + ' <strong>' + modelBits.join(' · ') + '</strong>' : '';
    $('sumOwner').textContent = ownerTypeLabel(state.vehicleData.ownerType);

    var driversCard = $('sumDriversCard');
    if (state.insuranceType === 'limited') {
      driversCard.classList.remove('hidden');
      $('sumDrivers').innerHTML = state.drivers.map(function (d) {
        return '<div class="driver-summary-row">' + escapeHtml(d.seria + d.number) + ' · ' + escapeHtml(relationLabel(d.relation)) + '</div>';
      }).join('');
    } else {
      driversCard.classList.add('hidden');
    }

    var endDate = addDaysYmd(state.startDate, durationDays(state.duration) - 1);
    $('sumDuration').textContent = fmtDate(state.startDate) + ' — ' + fmtDate(endDate);

    var gw = $('gatewayChoice');
    gw.innerHTML = '';
    ['CLICK', 'PAYME'].forEach(function (g) {
      var el = document.createElement('div');
      el.className = 'gateway-chip' + (state.gateway === g ? ' selected' : '');
      el.textContent = g;
      el.addEventListener('click', function () {
        state.gateway = g;
        renderStep6();
      });
      gw.appendChild(el);
    });

    $('priceDisplay').textContent = state.premium != null ? Math.round(state.premium).toLocaleString('ru-RU') + ' ' + t('priceCurrency') : '—';
  }

  // ---------------------------------------------------------------------
  // Validation
  // ---------------------------------------------------------------------

  function validateStep(step) {
    if (step === 1) {
      var ok = true;
      var plate = state.plateNumber.replace(/\s/g, '');
      if (!plate || plate.length < 6) {
        setErr('plate', t('errPlateIncomplete'));
        markInvalid('plateNumber', true);
        ok = false;
      } else { setErr('plate', ''); markInvalid('plateNumber', false); }

      if (state.techSeria.length < 3 || state.techNumber.length < 7) {
        setErr('tech', t('errTechIncomplete'));
        markInvalid('techSeria', true); markInvalid('techNumber', true);
        ok = false;
      } else { setErr('tech', ''); markInvalid('techSeria', false); markInvalid('techNumber', false); }
      return ok;
    }

    if (step === 2) {
      if (state.vehicleData.ownerType === 'PERSON') {
        if (state.physSeries.length < 2 || state.physNumber.length < 7) {
          setErr('physPass', t('errPassportIncomplete'));
          markInvalid('physSeries', true); markInvalid('physNumber', true);
          return false;
        }
        setErr('physPass', ''); markInvalid('physSeries', false); markInvalid('physNumber', false);
      }
      return true;
    }

    if (step === 3) {
      var ok3 = true;
      if (!state.insuranceType) { setErr('insurance', t('errInsuranceType')); ok3 = false; }
      else setErr('insurance', '');
      if (!state.phone || state.phone.length < 9) { setErr('phone', t('errPhoneIncomplete')); ok3 = false; }
      else setErr('phone', '');
      return ok3;
    }

    if (step === 5) {
      var ok5 = true;
      if (!state.startDate) { setErr('startDate', t('errStartDate')); ok5 = false; }
      else setErr('startDate', '');
      if (!state.duration) { setErr('duration', t('errDuration')); ok5 = false; }
      else setErr('duration', '');
      return ok5;
    }

    return true;
  }

  // ---------------------------------------------------------------------
  // Navigation
  // ---------------------------------------------------------------------

  function onNext() {
    if (state.submitting || state.checking) return;
    var step = state.step;
    if (!validateStep(step)) return;

    if (step === 1) {
      state.checking = true; render();
      api('vehicle', { plateNumber: state.plateNumber, techSeria: state.techSeria, techNumber: state.techNumber })
        .then(function (res) {
          state.checking = false;
          if (!res.success) { showToast(res.message || t('toastVehicleNotFound')); render(); return; }
          state.vehicleData = res;
          goToStep(2);
        })
        .catch(function () { state.checking = false; showToast(t('toastGenericError')); render(); });
      return;
    }

    if (step === 2) {
      if (state.vehicleData.ownerType === 'PERSON') {
        state.checking = true; render();
        api('owner', { seria: state.physSeries, number: state.physNumber, pinfl: state.vehicleData.pinfl })
          .then(function (res) {
            state.checking = false;
            if (!res.success) { showToast(res.message || t('toastOwnerNotFound')); render(); return; }
            state.ownerData = res;
            goToStep(3);
          })
          .catch(function () { state.checking = false; showToast(t('toastGenericError')); render(); });
        return;
      }
      goToStep(3);
      return;
    }

    if (step === 3) {
      goToStep(state.insuranceType === 'limited' ? 4 : 5);
      return;
    }

    if (step === 4) {
      if (state.drivers.length === 0) { setErr('drivers', t('errAtLeastOneDriver')); return; }
      var allOk = state.drivers.every(function (d) { return d.status === 'ok'; });
      if (!allOk) { setErr('drivers', t('errDriversIncomplete')); return; }
      setErr('drivers', '');
      goToStep(5);
      return;
    }

    if (step === 5) {
      state.checking = true; render();
      recomputePremium().then(function () {
        state.checking = false;
        goToStep(6);
      });
      return;
    }

    if (step === 6) {
      submitApplication();
      return;
    }
  }

  function onBack() {
    if (state.step === 5 && state.insuranceType !== 'limited') { goToStep(3); return; }
    if (state.step === 1) return;
    goToStep(state.step - 1);
  }

  function submitApplication() {
    state.submitting = true; render();

    var tg = window.Telegram && window.Telegram.WebApp;
    var initData = telegramInitData();

    var clientDebug = {
      hasTelegram: !!window.Telegram,
      hasWebApp: !!tg,
      version: tg ? tg.version : null,
      platform: tg ? tg.platform : null,
      initDataLength: initData ? initData.length : 0,
      initDataUnsafeUser: tg && tg.initDataUnsafe ? tg.initDataUnsafe.user : null,
      locationHash: location.hash,
      locationSearch: location.search,
      href: location.href,
    };

    var payload = {
      clientDebug: clientDebug,
      plateNumber: state.plateNumber,
      techSeria: state.techSeria,
      techNumber: state.techNumber,
      vehicleData: state.vehicleData,
      ownerData: state.vehicleData.ownerType === 'PERSON' ? {
        seria: state.physSeries,
        number: state.physNumber,
        birthDate: state.ownerData ? state.ownerData.birthDate : null,
        districtId: state.ownerData ? state.ownerData.districtId : null,
      } : null,
      insuranceType: state.insuranceType,
      phone: state.phone,
      drivers: state.insuranceType === 'limited'
        ? state.drivers.map(function (d) { return { seria: d.seria, number: d.number, birthDate: d.birthDate, relation: d.relation }; })
        : [],
      startDate: state.startDate,
      duration: state.duration,
      gateway: state.gateway,
    };

    api('submit', payload)
      .then(function (res) {
        state.submitting = false;
        if (!res.success) { showToast(res.message || t('toastGenericError')); render(); return; }
        showSuccess(res);
      })
      .catch(function () {
        state.submitting = false;
        showToast(t('toastSubmitError'));
        render();
      });
  }

  function showSuccess(res) {
    $('formScreen').classList.add('hidden');
    $('successScreen').classList.remove('hidden');
    $('successText').textContent = res.message || t('successDefaultMessage');
    if (res.paymentLink) {
      $('paymentLinkBtn').href = res.paymentLink;
      $('paymentLinkBtn').classList.remove('hidden');
    }
  }

  // ---------------------------------------------------------------------
  // Static wiring
  // ---------------------------------------------------------------------

  function wireStatic() {
    $('backBtn').addEventListener('click', onBack);
    $('nextBtn').addEventListener('click', onNext);

    $('plateNumber').addEventListener('input', function (e) {
      state.plateNumber = e.target.value.toUpperCase();
      e.target.value = state.plateNumber;
    });
    $('techSeria').addEventListener('input', function (e) {
      var wasFull = state.techSeria.length >= 3;
      state.techSeria = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');
      e.target.value = state.techSeria;
      autoAdvance(e, wasFull, state.techSeria.length >= 3, $('techNumber'));
    });
    $('techNumber').addEventListener('input', function (e) {
      state.techNumber = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = state.techNumber;
    });

    $('physSeries').addEventListener('input', function (e) {
      var wasFull = state.physSeries.length >= 2;
      state.physSeries = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');
      e.target.value = state.physSeries;
      autoAdvance(e, wasFull, state.physSeries.length >= 2, $('physNumber'));
    });
    $('physNumber').addEventListener('input', function (e) {
      state.physNumber = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = state.physNumber;
    });

    $('phoneInput').addEventListener('input', function (e) {
      state.phone = e.target.value.replace(/[^0-9]/g, '').slice(0, 9);
      e.target.value = state.phone;
    });

    $('addDriverBtn').addEventListener('click', function () {
      if (state.drivers.length >= 5) { showToast(t('toastMaxDrivers')); return; }
      state.drivers.push({
        id: 'd' + Date.now() + Math.random(), isOwner: false,
        seria: '', number: '', birthDate: '', relation: RELATIONS[0],
        status: 'idle', statusMsg: '', name: '',
      });
      renderStep4();
    });

    $('ownerIsDriverToggle').addEventListener('click', function () {
      if (state.ownerIsDriver) {
        state.ownerIsDriver = false;
        state.drivers = state.drivers.filter(function (d) { return !d.isOwner; });
      } else {
        state.ownerIsDriver = true;
        var ownerDriver = {
          id: 'owner', isOwner: true,
          seria: state.physSeries, number: state.physNumber,
          birthDate: state.ownerData ? isoToYmd(state.ownerData.birthDate) : '',
          relation: "O'zi", status: 'idle', statusMsg: '', name: '',
        };
        state.drivers.unshift(ownerDriver);
        maybeValidateDriver(ownerDriver);
      }
      renderStep4();
    });

    wireDateField($('startDateText'), $('startDateInput'), $('startDatePickBtn'), function (ymd) {
      state.startDate = ymd;
      setErr('startDate', '');
    });

    var gotoLinks = document.querySelectorAll('[data-goto]');
    for (var i = 0; i < gotoLinks.length; i++) {
      gotoLinks[i].addEventListener('click', function (e) {
        goToStep(parseInt(e.currentTarget.dataset.goto, 10));
      });
    }

    $('closeBtn').addEventListener('click', function () {
      if (window.Telegram && window.Telegram.WebApp) window.Telegram.WebApp.close();
    });
  }

  function init() {
    if (window.Telegram && window.Telegram.WebApp) {
      window.Telegram.WebApp.ready();
      window.Telegram.WebApp.expand();
    }

    api('init', {})
      .then(function (res) {
        state.lang = (res && res.lang === 'ru') ? 'ru' : 'uz';
      })
      .catch(function () {
        state.lang = 'uz';
      })
      .then(function () {
        wireStatic();
        applyStaticI18n();
        render();
      });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
