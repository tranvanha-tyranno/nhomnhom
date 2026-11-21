(function () {
  'use strict';
  const api = window.NhomNhom;
  const form = document.getElementById('booking-form');
  const list = document.getElementById('booking-list');
  const submit = document.getElementById('booking-submit');
  const message = document.getElementById('booking-message');
  const picker = document.getElementById('restaurant-picker');
  const options = document.getElementById('restaurant-options');
  const restaurantId = document.getElementById('booking-restaurant');
  const selected = document.getElementById('selected-restaurant');
  let csrfToken = '';
  let bookings = [];
  let restaurants = [];
  let filter = 'upcoming';
  let controller = null;
  let timer = null;
  function setReady() { submit.disabled = !csrfToken || !restaurantId.value; }
  function dateValue(value) { return new Date(value.replace(' ', 'T') + '+07:00'); }
  function renderBookings() {
    const filtered = bookings.filter(function (item) {
      if (filter === 'cancelled') return item.status === 'cancelled';
      return filter !== 'upcoming' || (item.status !== 'cancelled' && dateValue(item.bookingDate) > new Date());
    });
    const statuses = { pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', cancelled: 'Đã hủy' };
    list.innerHTML = filtered.length ? filtered.map(function (item) {
      const date = dateValue(item.bookingDate);
      const cancellable = item.status !== 'cancelled' && date > new Date();
      return '<article class="bookingRow"><div class="bookingRowTop"><span class="bookingReference">LỊCH HẸN #' + Number(item.id) + '</span><b class="status-' + api.escape(item.status) + '">' + api.escape(statuses[item.status] || item.status) + '</b></div><strong>' + api.escape(item.restaurantName) + '</strong><span>' + date.toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', dateStyle: 'medium', timeStyle: 'short' }) + ' · ' + Number(item.guestCount) + ' người</span>'
        + (cancellable ? '<div class="cancelControls"><button type="button" class="cancelBooking" data-confirm-cancel="' + Number(item.id) + '">Hủy lịch hẹn</button></div>' : '') + '</article>';
    }).join('') : '<p class="emptyState">' + (filter === 'upcoming' ? 'Chưa có cuộc hẹn sắp tới. Lên lịch một bữa ngon nhé!' : 'Chưa có lịch hẹn trong mục này.') + '</p>';
  }
  async function loadBookings() {
    try { const body = await api.request('api/bookings.php'); bookings = body.data; renderBookings(); }
    catch (error) { list.innerHTML = '<div class="emptyState" role="alert">' + api.escape(error.message) + '<button class="quickQuery" data-retry-bookings>Thử lại</button></div>'; }
  }
  async function searchRestaurants() {
    if (controller) controller.abort();
    if (!picker.value.trim()) { options.innerHTML = ''; return; }
    controller = new AbortController(); options.innerHTML = '<p>Đang tìm nhà hàng…</p>';
    try {
      const params = new URLSearchParams({ q: picker.value, page: '1', pageSize: '5' });
      const body = await api.request('api/restaurants.php?' + params, { signal: controller.signal }); restaurants = body.data;
      options.innerHTML = restaurants.length ? restaurants.map(function (item) {
        return '<button type="button" data-restaurant="' + Number(item.id) + '"><strong>' + api.escape(item.name) + '</strong><span>' + api.escape(item.address) + '</span></button>';
      }).join('') : '<p>Chưa tìm thấy quán. Thử một từ khóa khác.</p>';
    } catch (error) { if (error.name !== 'AbortError') options.innerHTML = '<p role="alert">' + api.escape(error.message) + '</p>'; }
  }
  picker.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(searchRestaurants, 300); });
  options.addEventListener('click', function (event) {
    const button = event.target.closest('[data-restaurant]'); if (!button) return;
    const item = restaurants.find(function (entry) { return String(entry.id) === button.dataset.restaurant; }); if (!item) return;
    restaurantId.value = item.id;
    selected.innerHTML = '<strong>' + api.escape(item.name) + '</strong><span>' + api.escape(item.address) + '</span><small>' + (item.isDemo ? 'Địa điểm mẫu · Yêu cầu trải nghiệm' : 'Yêu cầu cần được nhà hàng xác nhận') + '</small>';
    selected.hidden = false; options.innerHTML = ''; picker.value = ''; setReady();
    document.getElementById('booking-date').focus();
  });
  form.addEventListener('submit', async function (event) {
    event.preventDefault(); if (submit.disabled) return;
    submit.disabled = true; message.textContent = 'Đang gửi yêu cầu…';
    try {
      await api.post('api/bookings.php', { restaurantId: Number(restaurantId.value), guestName: document.getElementById('booking-name').value,
        email: document.getElementById('booking-email').value, bookingDate: document.getElementById('booking-date').value.replace('T', ' '),
        guestCount: Number(document.getElementById('booking-guests').value), note: document.getElementById('booking-note').value }, csrfToken);
      message.textContent = 'Đã gửi yêu cầu. Lịch đang chờ nhà hàng xác nhận.';
      document.getElementById('booking-date').value = ''; document.getElementById('booking-note').value = '';
      await loadBookings();
    } catch (error) { message.textContent = error.message; }
    finally { setReady(); }
  });
  list.addEventListener('click', async function (event) {
    if (event.target.closest('[data-retry-bookings]')) { loadBookings(); return; }
    const confirm = event.target.closest('[data-confirm-cancel]');
    if (confirm) {
      confirm.parentElement.innerHTML = '<span>Hủy cuộc hẹn này?</span><button class="cancelBooking" data-cancel="' + Number(confirm.dataset.confirmCancel) + '">Xác nhận hủy</button><button class="keepBooking" data-keep>Giữ lịch</button>'; return;
    }
    if (event.target.closest('[data-keep]')) { renderBookings(); return; }
    const button = event.target.closest('[data-cancel]'); if (!button) return;
    button.disabled = true;
    try { await api.post('api/bookings.php', { action: 'cancel', bookingId: Number(button.dataset.cancel) }, csrfToken); message.textContent = 'Đã hủy lịch hẹn.'; await loadBookings(); }
    catch (error) { message.textContent = error.message; button.disabled = false; }
  });
  document.querySelectorAll('[data-booking-filter]').forEach(function (button) {
    button.addEventListener('click', function () {
      filter = button.dataset.bookingFilter;
      document.querySelectorAll('[data-booking-filter]').forEach(function (other) { const active = other === button; other.classList.toggle('isActive', active); other.setAttribute('aria-pressed', String(active)); }); renderBookings();
    });
  });
  const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Ho_Chi_Minh', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
  document.getElementById('booking-date').min = today + 'T00:00';
  api.request('api/profile.php').then(function (body) { csrfToken = body.csrfToken; setReady(); }).catch(function (error) { message.textContent = error.message + ' Hãy tải lại trang để thử lại.'; });
  loadBookings();
}());
