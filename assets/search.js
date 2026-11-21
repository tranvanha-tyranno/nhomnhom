(function () {
  'use strict';
  const api = window.NhomNhom;
  const query = document.getElementById('query');
  const results = document.getElementById('results');
  const count = document.getElementById('result-count');
  const priceFilter = document.getElementById('price-filter');
  const sortFilter = document.getElementById('sort-filter');
  const travelMode = document.getElementById('travel-mode');
  const verifiedBtn = document.getElementById('verified-filter');
  const nearMeBtn = document.getElementById('near-me-btn');

  const state = {
    page: 1,
    mode: 'walking',
    priceLevel: '0',
    sort: 'relevance',
    verifiedOnly: false,
    origin: null,
    lastBody: null,
    controller: null,
    timer: null
  };

  function card(place, index, pagination) {
    const mapHref = api.directions(place, state.mode, state.origin);
    const action = place.isDemo
      ? '<span class="demoNotice">Địa điểm mẫu · Chưa xác minh</span>'
      : '<a class="mapButton" target="_blank" rel="noopener noreferrer" href="' + api.escape(mapHref) + '">Chỉ đường ↗</a>';
    let distanceBadge = '';
    if (state.origin) {
      const km = api.distanceKm(state.origin.lat, state.origin.lng, place.lat, place.lng);
      distanceBadge = '<span class="distanceBadge">~' + (km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km') + '</span>';
    }
    return '<article class="resultCard">'
      + '<div class="resultTopline">'
      + '<div class="resultToplineLeft"><span class="rankBadge">#' + ((pagination.page - 1) * pagination.pageSize + index + 1) + '</span><span class="priceBadge">' + api.priceTier(place.priceLevel) + '</span>' + distanceBadge + '</div>'
      + '<span class="ratingBadge">★ ' + Number(place.rating).toFixed(1) + ' <small>(' + Number(place.reviewCount || 0) + ')</small></span>'
      + '</div>'
      + '<div class="resultBody"><p class="cuisineLabel">' + api.escape(place.cuisine || 'Ẩm thực Việt') + '</p>'
      + '<h3><a class="cardTitleLink" href="restaurant.php?id=' + Number(place.id) + '">' + api.escape(place.name) + '</a></h3>'
      + '<p>' + api.escape(place.description) + '</p></div>'
      + '<div class="tagRow">' + (place.tags || []).slice(0, 3).map(function (tag) { return '<span>' + api.escape(tag) + '</span>'; }).join('') + '</div>'
      + '<div class="resultFooter"><span class="addressLine">' + api.escape(place.address) + '</span>'
      + '<div class="cardActionRow">' + action
      + '<a class="outlineButton" href="restaurant.php?id=' + Number(place.id) + '">Xem thực đơn</a>'
      + '<a class="outlineButton" href="bookings.php?restaurant=' + Number(place.id) + '">Đặt bàn</a>'
      + '</div></div></article>';
  }

  function render(body) {
    state.lastBody = body;
    const data = body.data || [];
    const p = body.pagination;
    count.textContent = Number(p.total).toLocaleString('vi-VN') + ' nhà hàng';
    if (!data.length) {
      results.innerHTML = '<div class="emptyState collectionEmpty"><strong>Chưa có quán phù hợp với bộ lọc này.</strong><span>Thử bỏ bớt bộ lọc hoặc đổi từ khóa khác nhé.</span></div>';
      return;
    }
    results.innerHTML = '<div class="resultsGrid">' + data.map(function (place, i) { return card(place, i, p); }).join('') + '</div>'
      + '<nav class="pagination" aria-label="Phân trang nhà hàng">'
      + '<button class="quickQuery" data-page="' + (p.page - 1) + '" ' + (p.page <= 1 ? 'disabled' : '') + '>← Trang trước</button>'
      + '<span>Trang ' + p.page + ' / ' + p.totalPages + '</span>'
      + '<button class="quickQuery" data-page="' + (p.page + 1) + '" ' + (p.page >= p.totalPages ? 'disabled' : '') + '>Trang sau →</button>'
      + '</nav>';
  }

  async function load() {
    if (state.controller) state.controller.abort();
    const controller = new AbortController();
    state.controller = controller;
    results.setAttribute('aria-busy', 'true');
    count.textContent = 'Đang tìm nhà hàng…';
    results.innerHTML = api.skeletonCards(6);
    try {
      const params = new URLSearchParams({
        q: query.value,
        page: String(state.page),
        pageSize: '6',
        priceLevel: state.priceLevel,
        sort: state.sort,
        verifiedOnly: state.verifiedOnly ? '1' : '0'
      });
      const body = await api.request('api/restaurants.php?' + params, { signal: controller.signal, headers: { Accept: 'application/json' } });
      render(body);
    } catch (error) {
      if (error.name === 'AbortError') return;
      count.textContent = 'Không tải được';
      results.innerHTML = '<div class="emptyState" role="alert">' + api.escape(error.message) + '<button class="quickQuery" id="retry-search">Thử lại</button></div>';
    } finally {
      if (state.controller === controller) results.setAttribute('aria-busy', 'false');
    }
  }

  document.getElementById('search-form').addEventListener('submit', function (event) {
    event.preventDefault(); clearTimeout(state.timer); state.page = 1; load();
  });
  query.addEventListener('input', function () {
    clearTimeout(state.timer); state.page = 1; state.timer = setTimeout(load, 250);
  });
  if (priceFilter) {
    priceFilter.addEventListener('change', function () { state.priceLevel = priceFilter.value; state.page = 1; load(); });
  }
  if (sortFilter) {
    sortFilter.addEventListener('change', function () { state.sort = sortFilter.value; state.page = 1; load(); });
  }
  if (travelMode) {
    travelMode.addEventListener('change', function () {
      state.mode = travelMode.value;
      if (state.lastBody) render(state.lastBody);
    });
  }
  if (verifiedBtn) {
    verifiedBtn.addEventListener('click', function () {
      state.verifiedOnly = !state.verifiedOnly;
      verifiedBtn.classList.toggle('isActive', state.verifiedOnly);
      verifiedBtn.setAttribute('aria-pressed', String(state.verifiedOnly));
      state.page = 1;
      load();
    });
  }
  if (nearMeBtn) {
    nearMeBtn.addEventListener('click', function () {
      if (state.origin) {
        state.origin = null;
        nearMeBtn.classList.remove('isActive');
        nearMeBtn.setAttribute('aria-pressed', 'false');
        api.toast('Đã tắt hiển thị khoảng cách.', 'info');
        if (state.lastBody) render(state.lastBody);
        return;
      }
      if (!navigator.geolocation) {
        api.toast('Trình duyệt không hỗ trợ định vị.', 'error');
        return;
      }
      nearMeBtn.disabled = true;
      navigator.geolocation.getCurrentPosition(function (pos) {
        state.origin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        nearMeBtn.disabled = false;
        nearMeBtn.classList.add('isActive');
        nearMeBtn.setAttribute('aria-pressed', 'true');
        api.toast('Đã cập nhật khoảng cách từ vị trí của bạn!', 'success');
        if (state.lastBody) render(state.lastBody);
      }, function () {
        nearMeBtn.disabled = false;
        api.toast('Không thể lấy vị trí. Hãy kiểm tra quyền truy cập vị trí của trình duyệt.', 'error');
      }, { timeout: 8000 });
    });
  }

  results.addEventListener('click', function (event) {
    const button = event.target.closest('[data-page], #retry-search');
    if (!button || button.disabled) return;
    if (button.dataset.page) state.page = Number(button.dataset.page);
    load();
  });

  document.querySelectorAll('[data-query]').forEach(function (button) {
    button.addEventListener('click', function () {
      clearTimeout(state.timer); query.value = button.dataset.query; state.page = 1;
      document.querySelectorAll('.moodButton').forEach(function (mood) { mood.setAttribute('aria-pressed', String(mood === button)); });
      load(); document.getElementById('restaurant-search').scrollIntoView({ block: 'start' });
    });
  });

  query.value = new URLSearchParams(location.search).get('q') || '';
  load();
}());
