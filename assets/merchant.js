(function () {
  'use strict';
  const api = window.NhomNhom;
  const storeForm = document.getElementById('create-store-form');
  const dishForm = document.getElementById('add-dish-form');
  if (!storeForm || !dishForm) return;

  const state = { csrf: '', options: null, restaurants: [], bookings: [] };

  const citySelect = document.getElementById('store-city');
  const storeCuisine = document.getElementById('store-cuisine');
  const dishRestaurant = document.getElementById('dish-restaurant');
  const dishCategory = document.getElementById('dish-category');
  const dishCuisine = document.getElementById('dish-cuisine');
  const storesList = document.getElementById('merchant-stores-list');
  const bookingsList = document.getElementById('merchant-bookings-list');
  const latInput = document.getElementById('store-lat');
  const lngInput = document.getElementById('store-lng');
  const mapPaste = document.getElementById('store-map-paste');
  const gpsBtn = document.getElementById('btn-fill-gps');

  function fillSelect(el, items, labelKey) {
    el.innerHTML = items.map(function (it) {
      return '<option value="' + Number(it.id) + '">' + api.escape(it[labelKey || 'name']) + '</option>';
    }).join('');
  }

  function renderStats() {
    const totalDishes = state.restaurants.reduce(function (sum, r) { return sum + Number(r.menuCount || 0); }, 0);
    document.getElementById('stat-stores').textContent = String(state.restaurants.length);
    document.getElementById('stat-dishes').textContent = String(totalDishes);
    document.getElementById('stat-bookings').textContent = String(state.bookings.length);
  }

  function syncDishRestaurantSelect() {
    const prev = dishRestaurant.value;
    if (!state.restaurants.length) {
      dishRestaurant.innerHTML = '<option value="">— Tạo quán ở Bước 1 trước —</option>';
      return;
    }
    dishRestaurant.innerHTML = state.restaurants.map(function (r) {
      return '<option value="' + Number(r.id) + '">' + api.escape(r.name) + '</option>';
    }).join('');
    if (prev && state.restaurants.some(function (r) { return String(r.id) === String(prev); })) {
      dishRestaurant.value = prev;
    }
  }

  function renderStores() {
    if (!state.restaurants.length) {
      storesList.innerHTML = '<div class="emptyState collectionEmpty"><strong>Bạn chưa tạo quán ăn nào.</strong><span>Hãy điền biểu mẫu "Tạo quán ăn mới" ở trên để quán xuất hiện ngay trên bản đồ & trang chủ.</span></div>';
      return;
    }
    storesList.innerHTML = state.restaurants.map(function (r) {
      const mapHref = api.directions(r, 'driving');
      const menuRows = (r.menu || []).length
        ? '<ul class="merchantMenuList">' + r.menu.map(function (d) {
            return '<li><div class="merchantDishInfo"><strong>' + api.escape(d.name) + '</strong><span>'
              + api.money(d.priceVnd) + ' · ' + (d.isVegetarian ? 'Món chay' : 'Món mặn')
              + (d.spiceLevel > 0 ? ' · Cay cấp ' + Number(d.spiceLevel) : ' · Không cay')
              + '</span></div><button type="button" class="outlineButton btnSmallDanger" data-remove-dish="'
              + Number(d.dishId) + '" data-restaurant="' + Number(r.id) + '">Xóa</button></li>';
          }).join('') + '</ul>'
        : '<p class="merchantEmptyMenu">Chưa có món ăn nào. Hãy dùng khung "Thêm món ăn mới" ở trên.</p>';

      return '<article class="merchantStoreCard">'
        + '<div class="merchantStoreHead"><div>'
        + '<span class="verifiedBadge">✓ Đã xác minh · Google Maps</span>'
        + '<h3><a class="cardTitleLink" href="restaurant.php?id=' + Number(r.id) + '">' + api.escape(r.name) + '</a></h3>'
        + '<p class="merchantStoreAddr">' + api.escape(r.address) + ' <small>(' + Number(r.lat).toFixed(4) + ', ' + Number(r.lng).toFixed(4) + ')</small></p>'
        + '</div><div class="merchantStoreLinks">'
        + '<a class="mapButton" target="_blank" rel="noopener noreferrer" href="' + api.escape(mapHref) + '">Mở Google Maps ↗</a>'
        + '<a class="outlineButton" href="restaurant.php?id=' + Number(r.id) + '">Xem trang quán</a>'
        + '</div></div>'
        + '<div class="merchantMenuWrap"><h4>Thực đơn hiện tại (' + Number(r.menuCount || 0) + ' món)</h4>' + menuRows + '</div>'
        + '</article>';
    }).join('');
  }

  function renderBookings() {
    if (!state.bookings.length) {
      bookingsList.innerHTML = '<div class="emptyState">Chưa có khách đặt bàn tại các quán của bạn.</div>';
      return;
    }
    const labels = { pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', cancelled: 'Đã hủy' };
    bookingsList.innerHTML = state.bookings.map(function (b) {
      const actions = b.status === 'pending'
        ? '<div class="merchantBookingActions">'
          + '<button type="button" class="likeButton btnSmall" data-booking-id="' + Number(b.id) + '" data-booking-status="confirmed">Xác nhận</button>'
          + '<button type="button" class="outlineButton btnSmallDanger" data-booking-id="' + Number(b.id) + '" data-booking-status="cancelled">Từ chối</button>'
          + '</div>'
        : '';
      return '<article class="merchantBookingCard"><div class="merchantBookingMain">'
        + '<div class="merchantBookingTop"><strong>' + api.escape(b.restaurantName) + '</strong><span class="statusPill status-' + api.escape(b.status) + '">' + api.escape(labels[b.status] || b.status) + '</span></div>'
        + '<p>Khách: <b>' + api.escape(b.guestName) + '</b> (' + api.escape(b.email) + ') · ' + Number(b.guestCount) + ' người · ' + api.escape(b.bookingDate) + '</p>'
        + (b.note ? '<small>Ghi chú: ' + api.escape(b.note) + '</small>' : '')
        + '</div>' + actions + '</article>';
    }).join('');
  }

  async function loadAll() {
    try {
      const body = await api.request('api/merchant.php');
      state.csrf = body.csrfToken;
      state.options = body.options;
      state.restaurants = body.restaurants || [];
      state.bookings = body.bookings || [];
      if (!citySelect.options.length && state.options) {
        fillSelect(citySelect, state.options.cities);
        fillSelect(storeCuisine, state.options.cuisines);
        fillSelect(dishCategory, state.options.categories);
        fillSelect(dishCuisine, state.options.cuisines);
        const firstCity = state.options.cities[0];
        if (firstCity && !latInput.value && !lngInput.value) {
          latInput.value = String(firstCity.latitude);
          lngInput.value = String(firstCity.longitude);
        }
      }
      renderStats();
      syncDishRestaurantSelect();
      renderStores();
      renderBookings();
    } catch (error) {
      api.toast(error.message, 'error');
    }
  }

  if (mapPaste) {
    mapPaste.addEventListener('input', function () {
      const val = mapPaste.value.trim();
      const match = val.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
      if (match) {
        latInput.value = match[1];
        lngInput.value = match[2];
        api.toast('Đã tách tọa độ GPS từ liên kết Google Maps!', 'success');
      }
    });
  }

  if (gpsBtn) {
    gpsBtn.addEventListener('click', function () {
      if (!navigator.geolocation) {
        api.toast('Trình duyệt không hỗ trợ định vị GPS.', 'error');
        return;
      }
      gpsBtn.disabled = true;
      navigator.geolocation.getCurrentPosition(function (pos) {
        latInput.value = pos.coords.latitude.toFixed(6);
        lngInput.value = pos.coords.longitude.toFixed(6);
        gpsBtn.disabled = false;
        api.toast('Đã điền tọa độ GPS hiện tại!', 'success');
      }, function () {
        gpsBtn.disabled = false;
        api.toast('Không thể lấy vị trí. Bạn có thể dán link Google Maps vào ô bên dưới.', 'error');
      }, { timeout: 8000 });
    });
  }

  storeForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    const btn = document.getElementById('btn-create-store');
    const msg = document.getElementById('store-form-msg');
    btn.disabled = true;
    msg.textContent = '';
    try {
      const fd = new FormData(storeForm);
      const res = await api.post('api/merchant.php', {
        action: 'create_restaurant',
        name: fd.get('name'),
        cityId: Number(fd.get('cityId')),
        areaName: fd.get('areaName'),
        address: fd.get('address'),
        latitude: Number(fd.get('latitude')),
        longitude: Number(fd.get('longitude')),
        cuisineId: Number(fd.get('cuisineId')),
        priceLevel: Number(fd.get('priceLevel')),
        description: fd.get('description'),
        tagIds: [2, 3, 5]
      }, state.csrf);
      storeForm.reset();
      mapPaste.value = '';
      msg.textContent = 'Đã tạo quán thành công! Quán đã xuất hiện trên trang chủ kèm chỉ đường Google Maps.';
      api.toast('Đã đăng quán “' + (res.data && res.data.name ? res.data.name : '') + '”!', 'success');
      await loadAll();
      if (res.data && res.data.id) dishRestaurant.value = String(res.data.id);
    } catch (error) {
      msg.textContent = error.message;
      api.toast(error.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  dishForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    const btn = document.getElementById('btn-add-dish');
    const msg = document.getElementById('dish-form-msg');
    btn.disabled = true;
    msg.textContent = '';
    try {
      const fd = new FormData(dishForm);
      const restaurantId = Number(fd.get('restaurantId'));
      if (!restaurantId) throw new Error('Vui lòng tạo quán ăn trước khi thêm món.');
      await api.post('api/merchant.php', {
        action: 'add_dish',
        restaurantId: restaurantId,
        dishName: fd.get('dishName'),
        categoryId: Number(fd.get('categoryId')),
        cuisineId: Number(fd.get('cuisineId')),
        priceVnd: Number(fd.get('priceVnd')),
        spiceLevel: Number(fd.get('spiceLevel')),
        isVegetarian: document.getElementById('dish-veg').checked ? 1 : 0,
        description: fd.get('description')
      }, state.csrf);
      document.getElementById('dish-name').value = '';
      document.getElementById('dish-desc').value = '';
      document.getElementById('dish-veg').checked = false;
      msg.textContent = 'Đã thêm món ăn vào thực đơn quán!';
      api.toast('Đã thêm món vào thực đơn!', 'success');
      await loadAll();
      dishRestaurant.value = String(restaurantId);
    } catch (error) {
      msg.textContent = error.message;
      api.toast(error.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  storesList.addEventListener('click', async function (event) {
    const btn = event.target.closest('[data-remove-dish]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    try {
      await api.post('api/merchant.php', {
        action: 'remove_dish',
        restaurantId: Number(btn.dataset.restaurant),
        dishId: Number(btn.dataset.removeDish)
      }, state.csrf);
      api.toast('Đã xóa món khỏi thực đơn.', 'info');
      await loadAll();
    } catch (error) {
      api.toast(error.message, 'error');
      btn.disabled = false;
    }
  });

  bookingsList.addEventListener('click', async function (event) {
    const btn = event.target.closest('[data-booking-id]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    try {
      await api.post('api/merchant.php', {
        action: 'booking_status',
        bookingId: Number(btn.dataset.bookingId),
        status: btn.dataset.bookingStatus
      }, state.csrf);
      api.toast(btn.dataset.bookingStatus === 'confirmed' ? 'Đã xác nhận lịch đặt bàn!' : 'Đã từ chối lịch đặt bàn.', 'success');
      await loadAll();
    } catch (error) {
      api.toast(error.message, 'error');
      btn.disabled = false;
    }
  });

  loadAll();
}());
