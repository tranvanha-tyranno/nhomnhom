(function () {
  'use strict';
  const ui = window.NhomRestaurantDialog;
  const known = new Map();
  const compared = new Map();
  const pending = new Set();
  let saved = [];
  let csrfToken = '';
  let savedReady = false;
  const filtersForm = document.getElementById('restaurant-filters');
  const message = document.getElementById('explore-message');

  function filters() {
    const values = Object.fromEntries(new FormData(filtersForm));
    values.maxPrice = values.maxPrice || '0';
    return values;
  }

  function remember(place) { known.set(String(place.id), place); }

  function cardActions(place, includeDetail) {
    const id = ui.escape(place.id);
    return '<div class="restaurantActions">' + (includeDetail === false ? '' : '<button class="quickQuery" data-detail="' + id + '">Thực đơn & chi tiết</button>')
      + '<button class="quickQuery" data-save="' + id + '" aria-pressed="false" disabled>Lưu quán</button>'
      + '<button class="quickQuery" data-compare="' + id + '" aria-pressed="false">So sánh</button></div>';
  }

  function syncButtons() {
    document.querySelectorAll('[data-save]').forEach(function (button) {
      const active = saved.some(function (p) { return p.id === button.dataset.save; });
      button.textContent = active ? '♥ Đã lưu · Bỏ lưu' : '♡ Lưu quán';
      button.setAttribute('aria-pressed', String(active));
      button.disabled = !savedReady || pending.size > 0;
    });
    document.querySelectorAll('[data-compare]').forEach(function (button) {
      const active = compared.has(button.dataset.compare);
      button.textContent = button.closest('#compare-selection') && active
        ? compared.get(button.dataset.compare).name + ' ×' : (active ? '✓ Đã chọn so sánh' : 'So sánh');
      button.setAttribute('aria-pressed', String(active));
    });
  }

  function renderSaved() {
    document.getElementById('saved-count').textContent = saved.length;
    document.getElementById('saved-list').innerHTML = saved.length ? saved.map(function (place) {
      const id = ui.escape(place.id);
      return '<article class="savedRestaurant"><div><h4>' + ui.escape(place.name) + '</h4><p>' + ui.escape(place.address)
        + '</p><small>' + (place.isActive ? 'Giá từ ' + ui.money(place.minPriceVnd) : 'Quán đã ngừng hoạt động') + '</small></div><div class="restaurantActions">'
        + (place.isActive ? '<button class="quickQuery" data-detail="' + id + '">Xem quán</button><button class="quickQuery" data-compare="' + id + '">So sánh</button>' : '')
        + '<button class="quickQuery" data-save="' + id + '">Bỏ lưu</button></div></article>';
    }).join('') : '<p>Chưa lưu quán nào. Nhấn “Lưu quán” ở kết quả tìm kiếm để xem lại tại đây.</p>';
    syncButtons();
  }

  async function requestSaved(payload) {
    const options = { headers: { Accept: 'application/json' } };
    if (payload) {
      options.method = 'POST';
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(Object.assign({}, payload, { csrfToken: csrfToken }));
    }
    const response = await fetch('api/saved.php', options);
    const body = await response.json();
    if (!response.ok || !body.success) throw new Error(body.error || 'Không thể tải quán đã lưu.');
    return body;
  }

  async function start() {
    try {
      const body = await requestSaved();
      csrfToken = body.csrfToken;
      saved = body.data;
      saved.forEach(remember);
      savedReady = true;
      renderSaved();
    } catch (error) {
      document.getElementById('saved-list').innerHTML = '<p role="alert">' + ui.escape(error.message) + '</p><button class="quickQuery" id="retry-saved">Thử lại</button>';
      message.textContent = 'Chưa tải được quán đã lưu. Mở mục “Quán đã lưu” để thử lại.';
    }
  }

  async function toggleSaved(id) {
    if (!savedReady || pending.size) return;
    const action = saved.some(function (p) { return p.id === id; }) ? 'remove' : 'save';
    pending.add(id);
    syncButtons();
    try {
      const body = await requestSaved({ restaurantId: Number(id), action: action });
      saved = body.data;
      saved.forEach(remember);
      renderSaved();
      announce(action === 'save' ? 'Đã lưu quán vào danh sách của bạn.' : 'Đã bỏ lưu quán.');
    } catch (error) { announce(error.message); }
    finally { pending.delete(id); syncButtons(); }
  }

  function announce(text) {
    message.textContent = text;
    document.getElementById('dialog-message').textContent = text;
  }

  function toggleCompared(id) {
    if (compared.has(id)) compared.delete(id);
    else if (compared.size >= 3) { announce('Chỉ so sánh tối đa 3 quán. Bỏ chọn một quán để thêm quán khác.'); return; }
    else if (known.has(id)) compared.set(id, known.get(id));
    document.getElementById('compare-selection').innerHTML = Array.from(compared.values()).map(function (p) {
      return '<button class="quickQuery" data-compare="' + ui.escape(p.id) + '" aria-label="Bỏ so sánh ' + ui.escape(p.name) + '">' + ui.escape(p.name) + ' ×</button>';
    }).join('');
    document.getElementById('compare-open').disabled = compared.size < 2;
    document.getElementById('compare-open').textContent = 'So sánh (' + compared.size + '/3)';
    syncButtons();
    announce('Đã chọn ' + compared.size + '/3 quán để so sánh.');
  }

  async function randomPick() {
    const button = document.getElementById('random-restaurant');
    button.disabled = true;
    message.textContent = 'Đang chọn quán theo bộ lọc…';
    const params = new URLSearchParams(Object.assign(filters(), { q: document.getElementById('query').value, sort: 'random', pageSize: '1' }));
    try {
      const response = await fetch('api/restaurants.php?' + params, { headers: { Accept: 'application/json' } });
      const body = await response.json();
      if (!response.ok || !body.success) throw new Error(body.error || 'Không thể chọn quán.');
      if (!body.data.length) { message.textContent = 'Không có quán phù hợp. Hãy nới bộ lọc rồi thử lại.'; return; }
      remember(body.data[0]);
      message.textContent = 'Gợi ý hôm nay: ' + body.data[0].name;
      ui.open(body.data[0].id);
    } catch (error) { message.textContent = error.message; }
    finally { button.disabled = false; }
  }

  filtersForm.addEventListener('submit', function (event) { event.preventDefault(); document.dispatchEvent(new Event('nomnom:filters')); });
  filtersForm.addEventListener('change', function () { if (filtersForm.checkValidity()) document.dispatchEvent(new Event('nomnom:filters')); });
  document.getElementById('reset-filters').addEventListener('click', function () { filtersForm.reset(); document.dispatchEvent(new Event('nomnom:filters')); });
  document.getElementById('random-restaurant').addEventListener('click', randomPick);
  document.getElementById('compare-open').addEventListener('click', function () { if (compared.size >= 2) ui.compare(Array.from(compared.values())); });
  document.addEventListener('click', function (event) {
    const target = event.target.closest('[data-detail], [data-save], [data-compare], #retry-saved');
    if (!target || target.disabled) return;
    if (target.dataset.detail) ui.open(target.dataset.detail);
    if (target.dataset.save) toggleSaved(target.dataset.save);
    if (target.dataset.compare) toggleCompared(target.dataset.compare);
    if (target.id === 'retry-saved') start();
  });
  window.NhomExplore = { filters: filters, cardActions: cardActions, remember: remember, syncButtons: syncButtons, start: start,
    onResults: function (places) { places.forEach(remember); syncButtons(); } };
}());
