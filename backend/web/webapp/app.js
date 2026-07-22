(function () {
  'use strict';

  var RELATIONS = ["O'zi", "Turmush o'rtog'i", "Farzandi", "Ota-onasi", "Boshqa qarindosh"];
  var DURATIONS = [
    { key: '20d', label: '20 kun', days: 20 },
    { key: '6m', label: '6 oy', days: 180 },
    { key: '1y', label: '1 yil', days: 365 },
  ];
  var STEP_TITLES = { 1: 'Transport', 2: 'Egasi', 3: "Sug'urta turi", 4: 'Haydovchilar', 5: 'Muddat', 6: 'Tasdiqlash' };

  var state = {
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

  function $(id) { return document.getElementById(id); }

  function todayYmd() { return new Date().toISOString().slice(0, 10); }

  function fmtDate(ymd) {
    if (!ymd) return '';
    var p = ymd.split('-');
    return p[2] + '.' + p[1] + '.' + p[0];
  }

  function durationDays(key) {
    var d = DURATIONS.filter(function (o) { return o.key === key; })[0];
    return d ? d.days : 0;
  }

  function addDaysYmd(ymd, days) {
    var d = new Date(ymd + 'T00:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  }

  function isoToYmd(iso) {
    try {
      var d = new Date(iso);
      if (isNaN(d.getTime())) return '';
      return d.toISOString().slice(0, 10);
    } catch (e) { return ''; }
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

  function telegramInitData() {
    var tg = window.Telegram && window.Telegram.WebApp;
    return tg ? tg.initData : '';
  }

  function api(action, body) {
    // initData is always attached here (not left to each call site) so every
    // endpoint can be verified server-side as coming from the bot's admin.
    var payload = Object.assign({}, body || {}, { initData: telegramInitData() });
    return fetch('/web-app/' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).then(function (r) { return r.json(); });
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

    $('stepLabel').textContent = 'Qadam ' + state.step + '/6';
    $('stepTitle').textContent = STEP_TITLES[state.step];
    $('progressBar').style.width = (state.step / 6 * 100) + '%';

    $('backBtn').classList.toggle('hidden', state.step === 1);
    $('nextBtn').textContent = state.step === 6 ? "To'lovga o'tish" : 'Davom etish';
    $('nextBtn').disabled = state.checking || state.submitting;

    $('checkingIndicator').classList.toggle('hidden', !(state.step === 1 && state.checking));

    if (state.step === 2) renderStep2();
    if (state.step === 3) renderStep3();
    if (state.step === 4) renderStep4();
    if (state.step === 5) renderStep5();
    if (state.step === 6) renderStep6();
  }

  function goToStep(n) { state.step = n; render(); }

  function renderStep2() {
    var vt = state.vehicleData.ownerType;
    $('ownerTypeLabel').textContent = vt === 'ORGANIZATION' ? 'Yuridik shaxs' : 'Jismoniy shaxs';
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
      ? 'Model: <strong>' + escapeHtml(state.vehicleData.model) + '</strong>' : '';
    $('vehicleTypeLine').innerHTML = state.vehicleData.vehicleTypeName
      ? 'Turi: <strong>' + escapeHtml(state.vehicleData.vehicleTypeName) + '</strong>' : '';
  }

  function renderStep3() {
    var container = $('insuranceCards');
    container.innerHTML = '';
    var cards = [
      { key: 'limited', title: 'Cheklangan', desc: "Faqat ro'yxatdagi haydovchilar transport vositasini boshqarishi mumkin" },
      { key: 'unlimited', title: 'Cheklanmagan', desc: 'Har qanday shaxs transport vositasini boshqarishi mumkin' },
    ];
    cards.forEach(function (c) {
      var el = document.createElement('div');
      el.className = 'ins-card' + (state.insuranceType === c.key ? ' selected' : '');
      el.innerHTML = '<div class="ins-top"><div class="ins-title">' + c.title + '</div><div class="ins-dot"></div></div>' +
        '<div class="ins-desc">' + c.desc + '</div>';
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
    if (d.status === 'err') return d.statusMsg || 'Xato';
    if (d.status === 'checking') return 'Tekshirilmoqda...';
    return '';
  }

  function renderStep4() {
    $('driversCountLabel').textContent = state.drivers.length + '/5 haydovchi';
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
      var label = d.isOwner ? 'Siz (avtomobil egasi)' : 'Haydovchi ' + (i + 1);
      var safeId = escapeHtml(d.id);
      card.innerHTML =
        '<div class="driver-head"><div class="name">' + label + '</div>' +
        (!d.isOwner ? '<div class="driver-remove" data-remove="' + safeId + '">✕</div>' : '') +
        '</div>' +
        '<div class="driver-row">' +
        '<input type="text" placeholder="Seriya" maxlength="2" data-field="seria" data-id="' + safeId + '" value="' + escapeHtml(d.seria) + '">' +
        '<input type="text" placeholder="Raqami" maxlength="7" data-field="number" data-id="' + safeId + '" value="' + escapeHtml(d.number) + '">' +
        '</div>' +
        '<input type="date" data-field="birthDate" data-id="' + safeId + '" value="' + escapeHtml(d.birthDate) + '" max="' + todayYmd() + '">' +
        '<select data-field="relation" data-id="' + safeId + '">' +
        RELATIONS.map(function (r) { return '<option value="' + escapeHtml(r) + '"' + (r === d.relation ? ' selected' : '') + '>' + escapeHtml(r) + '</option>'; }).join('') +
        '</select>' +
        '<div class="driver-status ' + statusClass(d.status) + '">' + escapeHtml(statusText(d)) + '</div>';
      container.appendChild(card);
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

    var value = e.target.value;
    if (field === 'seria') value = value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
    if (field === 'number') value = value.replace(/[^0-9]/g, '').slice(0, 7);
    driver[field] = value;
    e.target.value = value;

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
            driver.statusMsg = res.message || 'Xato';
          }
          updateDriverStatusDom(driver);
        })
        .catch(function () {
          driver.status = 'err';
          driver.statusMsg = 'Xatolik yuz berdi';
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
    var chips = $('durationChips');
    chips.innerHTML = '';
    DURATIONS.forEach(function (o) {
      var el = document.createElement('div');
      el.className = 'chip' + (state.duration === o.key ? ' selected' : '');
      el.textContent = o.label;
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
    $('sumPlate').innerHTML = 'Davlat raqami: <strong>' + escapeHtml(state.plateNumber) + '</strong>';
    $('sumTech').innerHTML = 'Texpassport: <strong>' + escapeHtml(state.techSeria + state.techNumber) + '</strong>';
    var modelBits = [];
    if (state.vehicleData.model) modelBits.push(escapeHtml(state.vehicleData.model));
    if (state.vehicleData.vehicleTypeName) modelBits.push(escapeHtml(state.vehicleData.vehicleTypeName));
    $('sumVehicleModel').innerHTML = modelBits.length ? 'Model: <strong>' + modelBits.join(' · ') + '</strong>' : '';
    $('sumOwner').textContent = state.vehicleData.ownerType === 'ORGANIZATION' ? 'Yuridik shaxs' : 'Jismoniy shaxs';

    var driversCard = $('sumDriversCard');
    if (state.insuranceType === 'limited') {
      driversCard.classList.remove('hidden');
      $('sumDrivers').innerHTML = state.drivers.map(function (d) {
        return '<div class="driver-summary-row">' + escapeHtml(d.seria + d.number) + ' · ' + escapeHtml(d.relation) + '</div>';
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

    $('priceDisplay').textContent = state.premium != null ? Math.round(state.premium).toLocaleString('ru-RU') + " so'm" : '—';
  }

  // ---------------------------------------------------------------------
  // Validation
  // ---------------------------------------------------------------------

  function validateStep(step) {
    if (step === 1) {
      var ok = true;
      var plate = state.plateNumber.replace(/\s/g, '');
      if (!plate || plate.length < 6) {
        setErr('plate', "Davlat raqamini to'liq kiriting");
        markInvalid('plateNumber', true);
        ok = false;
      } else { setErr('plate', ''); markInvalid('plateNumber', false); }

      if (state.techSeria.length < 3 || state.techNumber.length < 7) {
        setErr('tech', "Seriya (3 harf) va raqam (7 raqam) to'liq kiritilishi kerak");
        markInvalid('techSeria', true); markInvalid('techNumber', true);
        ok = false;
      } else { setErr('tech', ''); markInvalid('techSeria', false); markInvalid('techNumber', false); }
      return ok;
    }

    if (step === 2) {
      if (state.vehicleData.ownerType === 'PERSON') {
        if (state.physSeries.length < 2 || state.physNumber.length < 7) {
          setErr('physPass', "Passport ma'lumotlarini to'liq kiriting");
          markInvalid('physSeries', true); markInvalid('physNumber', true);
          return false;
        }
        setErr('physPass', ''); markInvalid('physSeries', false); markInvalid('physNumber', false);
      }
      return true;
    }

    if (step === 3) {
      var ok3 = true;
      if (!state.insuranceType) { setErr('insurance', "Sug'urta turini tanlang"); ok3 = false; }
      else setErr('insurance', '');
      if (!state.phone || state.phone.length < 9) { setErr('phone', "Telefon raqamini to'liq kiriting"); ok3 = false; }
      else setErr('phone', '');
      return ok3;
    }

    if (step === 5) {
      var ok5 = true;
      if (!state.startDate) { setErr('startDate', "Boshlanish sanasini tanlang"); ok5 = false; }
      else setErr('startDate', '');
      if (!state.duration) { setErr('duration', "Muddatni tanlang"); ok5 = false; }
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
          if (!res.success) { showToast(res.message || "Transport topilmadi"); render(); return; }
          state.vehicleData = res;
          goToStep(2);
        })
        .catch(function () { state.checking = false; showToast('Xatolik yuz berdi'); render(); });
      return;
    }

    if (step === 2) {
      if (state.vehicleData.ownerType === 'PERSON') {
        state.checking = true; render();
        api('owner', { seria: state.physSeries, number: state.physNumber, pinfl: state.vehicleData.pinfl })
          .then(function (res) {
            state.checking = false;
            if (!res.success) { showToast(res.message || 'Egasi topilmadi'); render(); return; }
            state.ownerData = res;
            goToStep(3);
          })
          .catch(function () { state.checking = false; showToast('Xatolik yuz berdi'); render(); });
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
      if (state.drivers.length === 0) { setErr('drivers', "Kamida bitta haydovchi qo'shing"); return; }
      var allOk = state.drivers.every(function (d) { return d.status === 'ok'; });
      if (!allOk) { setErr('drivers', "Barcha haydovchilar ma'lumotini to'liq va to'g'ri kiriting"); return; }
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
        if (!res.success) { showToast(res.message || 'Xatolik yuz berdi'); render(); return; }
        showSuccess(res);
      })
      .catch(function () {
        state.submitting = false;
        showToast("Xatolik yuz berdi. Qayta urinib ko'ring");
        render();
      });
  }

  function showSuccess(res) {
    $('formScreen').classList.add('hidden');
    $('successScreen').classList.remove('hidden');
    $('successText').textContent = res.message || "So'rovingiz qabul qilindi.";
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
      state.techSeria = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');
      e.target.value = state.techSeria;
    });
    $('techNumber').addEventListener('input', function (e) {
      state.techNumber = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = state.techNumber;
    });

    $('physSeries').addEventListener('input', function (e) {
      state.physSeries = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');
      e.target.value = state.physSeries;
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
      if (state.drivers.length >= 5) { showToast("Ko'pi bilan 5 ta haydovchi qo'shish mumkin"); return; }
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

    $('startDateInput').addEventListener('change', function (e) {
      state.startDate = e.target.value;
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
    wireStatic();
    render();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
