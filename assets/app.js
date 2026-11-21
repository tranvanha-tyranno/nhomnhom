(function () {
  'use strict';
  const state = { query: '', page: 1, pageSize: 12, mode: 'driving', timer: null, controller: null };
  const queryInput = document.getElementById('query');
  const results = document.getElementById('results');
  const resultCount = document.getElementById('result-count');
  const discoveryCard = document.getElementById('discovery-card');
  const tasteCount = document.getElementById('taste-count');
  const discoveryState = { items: [], index: 0, offset: 0, loading: false };
  let csrfToken = '';
  let activeGroupCode = '';

  function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, function (character) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character];
    });
  }

  function directionsUrl(place) {
    const params = new URLSearchParams({ api: '1', destination: place.lat + ',' + place.lng, travelmode: state.mode });
    return 'https://www.google.com/maps/dir/?' + params.toString();
  }

  function card(place, index, pagination) {
    const tags = (place.tags || []).slice(0, 3).map(function (tag) { return '<span>' + escapeHtml(tag) + '</span>'; }).join('');
    const action = place.isDemo
      ? '<span class="demoNotice">Địa điểm mẫu · Chưa xác minh</span>'
      : '<a class="mapButton" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(directionsUrl(place)) + '">↗ <span>Chỉ đường</span></a>';
    return '<article class="resultCard"><div class="resultTopline"><span class="rankBadge">#' + ((pagination.page - 1) * pagination.pageSize + index + 1) + '</span><span class="ratingBadge">★ ' + Number(place.rating).toFixed(1) + '</span></div>'
      + '<div class="resultBody"><p class="cuisineLabel">' + escapeHtml(place.cuisine) + '</p><h3>' + escapeHtml(place.name) + '</h3><p>' + escapeHtml(place.description) + '</p></div>'
      + '<div class="tagRow" aria-label="Thẻ món ăn">' + tags + '</div><div class="resultFooter"><span class="addressLine">📍 ' + escapeHtml(place.address) + '</span>' + action + '</div></article>';
  }

  function render(data) {
    const places = data.data || [];
    resultCount.innerHTML = '📍 <span>' + Number(data.pagination.total || 0).toLocaleString('vi-VN') + ' điểm ăn</span>';
    if (!places.length) { results.innerHTML = '<p class="emptyState">Chưa có món phù hợp.</p>'; return; }
    results.innerHTML = '<div class="resultsGrid">' + places.map(function (place, index) { return card(place, index, data.pagination); }).join('') + '</div>'
      + '<nav class="pagination" aria-label="Phân trang kết quả"><button class="quickQuery pageButton" data-page="' + (data.pagination.page - 1) + '" ' + (data.pagination.page <= 1 ? 'disabled' : '') + '>Trang trước</button><span>Trang ' + data.pagination.page + ' / ' + data.pagination.totalPages + '</span><button class="quickQuery pageButton" data-page="' + (data.pagination.page + 1) + '" ' + (data.pagination.page >= data.pagination.totalPages ? 'disabled' : '') + '>Trang sau</button></nav>';
  }

  function showError(message) { resultCount.innerHTML = '📍 <span>Không tải được</span>'; results.innerHTML = '<div class="emptyState" role="alert">' + escapeHtml(message) + ' <button class="quickQuery" id="retry">Thử lại</button></div>'; }
  function likedDishes() {
    try { return JSON.parse(localStorage.getItem('nomnom-liked-dishes') || '[]'); } catch (error) { return []; }
  }

  function apiPost(url, payload) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(Object.assign({}, payload, { csrfToken: csrfToken })) })
      .then(function (response) { return response.json().then(function (body) { if (!response.ok || body.success !== true) throw new Error(body.error || 'Không thể lưu dữ liệu.'); return body; }); });
  }

  function loadProfile() {
    return fetch('api/profile.php', { headers: { Accept: 'application/json' } }).then(function (response) { return response.json(); }).then(function (body) {
      if (body.success !== true) throw new Error(body.error || 'Không thể tải hồ sơ.');
      csrfToken = body.csrfToken;
      document.getElementById('profile-name').value = body.data.displayName;
      document.getElementById('profile-budget').value = body.data.maxPriceVnd || '';
      document.getElementById('profile-vegetarian').checked = body.data.vegetarianOnly;
      document.getElementById('profile-mode').value = body.data.travelMode;
    }).catch(function (error) { document.getElementById('profile-message').textContent = error.message; });
  }

  function renderInsights(items) {
    const list = document.getElementById('insights-list');
    if (!items.length) { list.innerHTML = '<p class="emptyState">Chưa có dữ liệu xu hướng.</p>'; return; }
    list.innerHTML = items.slice(0, 6).map(function (item, index) { return '<div class="insightRow"><span class="rankBadge">#' + (index + 1) + '</span><div><strong>' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(item.address) + '</small></div><b>' + item.score + ' điểm</b></div>'; }).join('');
  }

  function loadInsights() { fetch('api/insights.php', { headers: { Accept: 'application/json' } }).then(function (response) { return response.json(); }).then(function (body) { if (body.success !== true) throw new Error(body.error || 'Không thể tải xu hướng.'); renderInsights(body.data); }).catch(function (error) { document.getElementById('insights-list').innerHTML = '<p class="emptyState">' + escapeHtml(error.message) + '</p>'; }); }

  function showGroupSummary(data) {
    activeGroupCode = data.inviteCode;
    document.getElementById('group-code').value = data.inviteCode;
    document.getElementById('group-message').textContent = 'Mã nhóm: ' + data.inviteCode + ' · ' + data.memberCount + ' thành viên';
    document.getElementById('group-result').innerHTML = (data.matches || []).slice(0, 5).map(function (item) { return '<div><strong>' + escapeHtml(item.name) + '</strong><span>' + item.likes + '/' + data.memberCount + ' thích</span></div>'; }).join('') || '<small>Chưa có món được bình chọn chung.</small>';
  }

  function bindFeatureForms() {
    document.getElementById('profile-form').addEventListener('submit', function (event) { event.preventDefault(); const message = document.getElementById('profile-message'); apiPost('api/profile.php', { displayName: document.getElementById('profile-name').value, maxPriceVnd: Number(document.getElementById('profile-budget').value || 0), vegetarianOnly: document.getElementById('profile-vegetarian').checked, travelMode: document.getElementById('profile-mode').value }).then(function () { message.textContent = 'Đã lưu khẩu vị.'; }).catch(function (error) { message.textContent = error.message; }); });
    document.getElementById('booking-form').addEventListener('submit', function (event) { event.preventDefault(); const message = document.getElementById('booking-message'); apiPost('api/bookings.php', { restaurantId: Number(document.getElementById('booking-restaurant').value), guestName: document.getElementById('booking-name').value, email: document.getElementById('booking-email').value, bookingDate: document.getElementById('booking-date').value.replace('T', ' '), guestCount: Number(document.getElementById('booking-guests').value), note: document.getElementById('booking-note').value }).then(function () { message.textContent = 'Đã gửi yêu cầu đặt bàn.'; }).catch(function (error) { message.textContent = error.message; }); });
    document.getElementById('create-group').addEventListener('click', function () { apiPost('api/groups.php', { action: 'create', displayName: document.getElementById('group-name').value || 'Bạn' }).then(function (body) { showGroupSummary(body.data); }).catch(function (error) { document.getElementById('group-message').textContent = error.message; }); });
    document.getElementById('join-group').addEventListener('click', function () { apiPost('api/groups.php', { action: 'join', inviteCode: document.getElementById('group-code').value, displayName: document.getElementById('group-name').value || 'Bạn' }).then(function (body) { showGroupSummary(body.data); }).catch(function (error) { document.getElementById('group-message').textContent = error.message; }); });
  }

  function updateTasteCount() {
    tasteCount.textContent = 'Đã thích ' + likedDishes().length + ' món';
  }

  function discoveryUrl(place) {
    const params = new URLSearchParams({ api: '1', destination: place.restaurant.lat + ',' + place.restaurant.lng, travelmode: state.mode });
    return 'https://www.google.com/maps/dir/?' + params.toString();
  }

  function renderDiscoveryCard() {
    const place = discoveryState.items[discoveryState.index];
    if (!place) {
      discoveryCard.innerHTML = '<div class="emptyState">Bạn đã xem hết lượt gợi ý này. <button class="quickQuery" data-discovery-action="reload">Xem lượt mới</button></div>';
      return;
    }
    const dietary = place.isVegetarian ? 'Món chay' : 'Món mặn';
    const spice = place.spiceLevel > 0 ? 'Cay cấp ' + place.spiceLevel : 'Không cay';
    const price = Number(place.priceVnd || 0).toLocaleString('vi-VN') + ' đ';
    const sample = place.isDemo ? '<span class="demoNotice">Địa điểm mẫu · Chưa xác minh</span>' : '<a class="mapButton discoveryMap" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(discoveryUrl(place)) + '">↗ Chỉ đường</a>';
    discoveryCard.innerHTML = '<div class="discoveryDish"><div class="discoveryDishTop"><span class="rankBadge">Món ' + (discoveryState.index + 1) + '</span><span class="ratingBadge">★ ' + Number(place.restaurant.rating).toFixed(1) + '</span></div>'
      + '<p class="cuisineLabel">' + escapeHtml(place.cuisine || dietary) + '</p><h3>' + escapeHtml(place.name) + '</h3><p class="discoveryDescription">' + escapeHtml(place.description) + '</p>'
      + '<div class="tagRow"><span>' + escapeHtml(dietary) + '</span><span>' + escapeHtml(spice) + '</span><span>' + escapeHtml(price) + '</span></div>'
      + '<div class="discoveryRestaurant"><strong>' + escapeHtml(place.restaurant.name) + '</strong><span>📍 ' + escapeHtml(place.restaurant.address) + '</span></div>'
      + '<div class="discoveryActions"><button class="skipButton" data-discovery-action="skip">Bỏ qua</button><button class="likeButton" data-discovery-action="like">♥ Thích món này</button></div><div class="discoveryFooter">' + sample + '</div></div>';
  }

  function loadDiscovery(reset) {
    if (discoveryState.loading) return;
    if (reset) { discoveryState.items = []; discoveryState.index = 0; discoveryState.offset = 0; }
    discoveryState.loading = true;
    if (!discoveryState.items.length) discoveryCard.innerHTML = '<p class="emptyState">Đang tải món gợi ý…</p>';
    const params = new URLSearchParams({ limit: '8', offset: String(discoveryState.offset) });
    fetch('api/discover.php?' + params.toString(), { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.json().then(function (body) { if (!response.ok || body.success !== true) throw new Error(body.error || 'Không thể tải món gợi ý.'); return body; }); })
      .then(function (body) { discoveryState.items = discoveryState.items.concat(body.data || []); discoveryState.offset += (body.data || []).length; renderDiscoveryCard(); })
      .catch(function (error) { discoveryCard.innerHTML = '<div class="emptyState" role="alert">' + escapeHtml(error.message) + ' <button class="quickQuery" data-discovery-action="reload">Thử lại</button></div>'; })
      .finally(function () { discoveryState.loading = false; });
  }

  function handleDiscoveryAction(action) {
    const place = discoveryState.items[discoveryState.index];
    if (action === 'reload') { loadDiscovery(true); return; }
    if (!place) return;
    apiPost('api/taste.php', { dishId: Number(place.id), action: action === 'like' ? 'like' : 'skip' }).catch(function (error) { console.warn('Taste sync failed:', error.message); });
    if (action === 'like') {
      const liked = likedDishes().filter(function (item) { return item.id !== place.id; });
      liked.push({ id: place.id, name: place.name, restaurantId: place.restaurant.id, savedAt: new Date().toISOString() });
      localStorage.setItem('nomnom-liked-dishes', JSON.stringify(liked.slice(-50)));
      updateTasteCount();
    }
    discoveryState.index += 1;
    if (discoveryState.index >= discoveryState.items.length - 2) loadDiscovery(false);
    renderDiscoveryCard();
  }

  function load() {
    if (state.controller) state.controller.abort();
    state.controller = new AbortController();
    resultCount.innerHTML = '📍 <span>Đang tải…</span>'; results.innerHTML = '<p class="emptyState">Đang tìm quán ăn…</p>';
    const params = new URLSearchParams({ q: state.query, page: String(state.page), pageSize: String(state.pageSize) });
    fetch('api/restaurants.php?' + params.toString(), { signal: state.controller.signal, headers: { Accept: 'application/json' } })
      .then(function (response) { return response.json().then(function (body) { if (!response.ok || body.success !== true) throw new Error(body.error || 'Không thể tải danh sách quán ăn.'); return body; }); })
      .then(render).catch(function (error) { if (error.name !== 'AbortError') showError(error.message); });
  }
  function scheduleLoad() { clearTimeout(state.timer); state.timer = setTimeout(load, 250); }

  queryInput.addEventListener('input', function () { state.query = queryInput.value; state.page = 1; scheduleLoad(); });
  document.getElementById('search-form').addEventListener('submit', function (event) { event.preventDefault(); state.query = queryInput.value; state.page = 1; load(); });
  document.addEventListener('click', function (event) {
    const target = event.target.closest('[data-query], [data-page], [data-mode], [data-discovery-action], #retry');
    if (!target) return;
    if (target.dataset.query !== undefined) { queryInput.value = target.dataset.query; state.query = target.dataset.query; state.page = 1; load(); }
    if (target.dataset.page !== undefined && !target.disabled) { state.page = Number(target.dataset.page); load(); }
    if (target.dataset.mode) { state.mode = target.dataset.mode; document.querySelectorAll('.modeButton').forEach(function (button) { const active = button.dataset.mode === state.mode; button.classList.toggle('isActive', active); button.setAttribute('aria-pressed', String(active)); }); renderCurrentLinks(); }
    if (target.dataset.discoveryAction) handleDiscoveryAction(target.dataset.discoveryAction);
    if (target.id === 'retry') load();
  });
  function renderCurrentLinks() { document.querySelectorAll('.mapButton').forEach(function (link) { const url = new URL(link.href); url.searchParams.set('travelmode', state.mode); link.href = url.toString(); }); }
  updateTasteCount();
  bindFeatureForms();
  loadProfile().then(function () { loadDiscovery(true); });
  loadInsights();
  load();
}());
