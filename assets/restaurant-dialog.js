(function () {
  'use strict';
  const dialog = document.getElementById('restaurant-dialog');
  const body = document.getElementById('restaurant-dialog-body');
  let requestNumber = 0;
  const escape = function (value) {
    return String(value).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c]; });
  };
  const money = function (value) { return value === null ? 'Chưa có giá' : Number(value).toLocaleString('vi-VN') + ' đ'; };

  function time(minute) {
    return String(Math.floor(minute / 60)).padStart(2, '0') + ':' + String(minute % 60).padStart(2, '0');
  }

  function hoursMarkup(hours) {
    const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    if (!hours.length) return '<p>Chưa cập nhật giờ mở cửa.</p>';
    return '<dl class="openingHours">' + hours.map(function (hour) {
      return '<div><dt>' + days[hour.weekday] + '</dt><dd>' + time(hour.opensMinute) + ' – ' + time(hour.closesMinute) + '</dd></div>';
    }).join('') + '</dl>';
  }

  function menuMarkup(menu) {
    if (!menu.length) return '<p>Chưa có món đang bán.</p>';
    return '<ul class="menuList">' + menu.map(function (dish) {
      return '<li><div><strong>' + escape(dish.name) + '</strong><p>' + escape(dish.description) + '</p><small>'
        + (dish.isVegetarian ? 'Món chay' : 'Món mặn') + ' · ' + (dish.spiceLevel ? 'Cay cấp ' + dish.spiceLevel : 'Không cay')
        + '</small></div><b>' + money(dish.priceVnd) + '</b></li>';
    }).join('') + '</ul>';
  }

  function detailMarkup(place) {
    const mode = document.querySelector('.modeButton.isActive').dataset.mode;
    const params = new URLSearchParams({ api: '1', destination: place.lat + ',' + place.lng, travelmode: mode });
    const action = place.isDemo ? '<p class="demoNotice">Dữ liệu mẫu: địa chỉ, giá và giờ mở cửa chưa được xác minh.</p>'
      : '<a class="mapButton" href="https://www.google.com/maps/dir/?' + escape(params.toString()) + '" target="_blank" rel="noopener noreferrer">Chỉ đường đến quán</a>';
    return '<p class="cuisineLabel">' + escape(place.cuisine) + '</p><h3>' + escape(place.name) + '</h3><p>' + escape(place.address) + '</p>'
      + '<p>★ ' + place.rating.toFixed(1) + ' · ' + place.reviewCount + ' đánh giá · Giá từ ' + money(place.minPriceVnd) + '</p>'
      + '<p>' + escape(place.description) + '</p><div class="tagRow">' + place.amenities.map(function (name) { return '<span>' + escape(name) + '</span>'; }).join('') + '</div>'
      + action + window.NhomExplore.cardActions(place, false) + '<h4>Giờ mở cửa</h4>' + hoursMarkup(place.hours)
      + '<h4>Thực đơn đang bán (' + place.menu.length + ' món)</h4>' + menuMarkup(place.menu);
  }

  function show(title) {
    document.getElementById('restaurant-dialog-title').textContent = title;
    document.getElementById('dialog-message').textContent = '';
    if (!dialog.open) dialog.showModal();
  }

  async function openDetail(id) {
    const request = ++requestNumber;
    show('Chi tiết quán');
    body.innerHTML = '<p role="status">Đang tải thực đơn…</p>';
    try {
      const response = await fetch('api/restaurant.php?' + new URLSearchParams({ id: id }), { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.error || 'Không thể tải thực đơn.');
      if (request !== requestNumber || !dialog.open) return;
      window.NhomExplore.remember(payload.data);
      body.innerHTML = detailMarkup(payload.data);
      window.NhomExplore.syncButtons();
    } catch (error) {
      if (request === requestNumber && dialog.open) body.innerHTML = '<p role="alert">' + escape(error.message) + '</p><button class="quickQuery" data-detail="' + escape(id) + '">Thử lại</button>';
    }
  }

  function compare(places) {
    ++requestNumber;
    show('So sánh quán');
    const fields = [
      ['Đánh giá', function (p) { return '★ ' + p.rating.toFixed(1) + ' (' + p.reviewCount + ' lượt)'; }],
      ['Giá món từ', function (p) { return money(p.minPriceVnd); }],
      ['Phân khúc', function (p) { return ['','Bình dân','Vừa phải','Cao cấp','Sang trọng'][p.priceLevel]; }],
      ['Địa chỉ', function (p) { return p.address; }],
      ['Dữ liệu', function (p) { return p.isDemo ? 'Quán mẫu · chưa xác minh' : 'Quán thực tế'; }]
    ];
    body.innerHTML = '<div class="compareScroll"><table class="compareTable"><caption>Giá thấp nhất của món đang bán; không phải giá trung bình mỗi người.</caption><thead><tr><th scope="col">Tiêu chí</th>'
      + places.map(function (p) { return '<th scope="col">' + escape(p.name) + '</th>'; }).join('') + '</tr></thead><tbody>'
      + fields.map(function (field) { return '<tr><th scope="row">' + field[0] + '</th>' + places.map(function (p) { return '<td>' + escape(field[1](p)) + '</td>'; }).join('') + '</tr>'; }).join('')
      + '<tr><th scope="row">Thực đơn</th>' + places.map(function (p) { return '<td><button class="quickQuery" data-detail="' + escape(p.id) + '">Xem thực đơn</button></td>'; }).join('') + '</tr></tbody></table></div>';
  }

  document.getElementById('close-restaurant-dialog').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('close', function () { ++requestNumber; });
  window.NhomRestaurantDialog = { open: openDetail, compare: compare, escape: escape, money: money };
}());
