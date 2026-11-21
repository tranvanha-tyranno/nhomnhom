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

  const ICONS = {
    pin: '<svg class="cardMiniIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z"/><circle cx="12" cy="10" r="2.2"/></svg>',
    check: '<svg class="cardMiniIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    nav: '<svg class="cardMiniIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>',
    menu: '<svg class="cardMiniIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>',
    calendar: '<svg class="cardMiniIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>',
    leafArt: '<svg class="coverWatermark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 50C14 26 30 14 52 14c0 22-12 38-38 36z"/><path d="M14 50c10-10 20-18 32-24"/></svg>',
    bowlArt: '<svg class="coverWatermark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 30h44a22 22 0 0 1-44 0z"/><path d="M22 54h20M22 14l26 8M26 22l22 4"/></svg>',
    plateArt: '<svg class="coverWatermark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="32" cy="34" r="20"/><circle cx="32" cy="34" r="12"/><path d="M10 14v12M54 14v12"/></svg>'
  };

  function cuisineTheme(place) {
    const text = (String(place.cuisine || '') + ' ' + String(place.name || '')).toLowerCase();
    if (text.indexOf('chay') !== -1) return { tone: 'tone-veg', art: ICONS.leafArt };
    if (text.indexOf('phở') !== -1 || text.indexOf('bún') !== -1 || text.indexOf('mì') !== -1 || text.indexOf('hủ tiếu') !== -1) {
      return { tone: 'tone-noodle', art: ICONS.bowlArt };
    }
    if (text.indexOf('trung') !== -1) return { tone: 'tone-central', art: ICONS.bowlArt };
    return { tone: 'tone-south', art: ICONS.plateArt };
  }

  function card(place, index, pagination) {
    const mapHref = api.directions(place, state.mode, state.origin);
    const theme = cuisineTheme(place);
    const rankNum = (pagination.page - 1) * pagination.pageSize + index + 1;
    const statusPill = place.isDemo
      ? '<span class="samplePill">Địa điểm mẫu</span>'
      : '<span class="verifiedBadge">' + ICONS.check + ' Đã xác minh</span>';
    let distanceBadge = '';
    if (state.origin) {
      const km = api.distanceKm(state.origin.lat, state.origin.lng, place.lat, place.lng);
      distanceBadge = '<span class="distanceBadge">~' + (km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km') + '</span>';
    }
    const primaryAction = place.isDemo
      ? '<div class="demoNotice cardDemoNotice">Địa điểm mẫu · Chưa có chỉ đường</div>'
      : '<a class="mapButton cardPrimaryCta" target="_blank" rel="noopener noreferrer" href="' + api.escape(mapHref) + '">' + ICONS.nav + '<span>Chỉ đường Google Maps</span><span class="ctaArrow" aria-hidden="true">↗</span></a>';

    return '<article class="resultCard editorialCard' + (place.isDemo ? ' isSampleCard' : ' isVerifiedCard') + '">'
      + '<div class="cardCover ' + theme.tone + '">'
      + '<div class="cardCoverTop">'
      + '<div class="cardCoverBadges"><span class="rankBadge">#' + rankNum + '</span>' + statusPill + distanceBadge + '</div>'
      + '<span class="ratingBadge">★ ' + Number(place.rating).toFixed(1) + ' <small>(' + Number(place.reviewCount || 0) + ')</small></span>'
      + '</div>'
      + theme.art
      + '</div>'
      + '<div class="cardInner">'
      + '<div class="cardMetaRow"><span class="cuisineLabel">' + api.escape(place.cuisine || 'Ẩm thực Việt') + '</span><span class="priceBadge">' + api.priceTier(place.priceLevel) + '</span></div>'
      + '<h3><a class="cardTitleLink" href="restaurant.php?id=' + Number(place.id) + '">' + api.escape(place.name) + '</a></h3>'
      + '<p class="cardSummary">' + api.escape(place.description) + '</p>'
      + '<div class="tagRow">' + (place.tags || []).slice(0, 3).map(function (tag) { return '<span>' + api.escape(tag) + '</span>'; }).join('') + '</div>'
      + '</div>'
      + '<div class="resultFooter">'
      + '<div class="addressRow">' + ICONS.pin + '<span class="addressLine">' + api.escape(place.address) + '</span></div>'
      + primaryAction
      + '<div class="cardSecondaryRow">'
      + '<a class="cardSecBtn" href="restaurant.php?id=' + Number(place.id) + '">' + ICONS.menu + '<span>Xem thực đơn</span></a>'
      + '<a class="cardSecBtn isBook" href="bookings.php?restaurant=' + Number(place.id) + '">' + ICONS.calendar + '<span>Đặt bàn</span></a>'
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
      document.querySelectorAll('.quickQueryRow .quickQuery').forEach(function (chip) { chip.classList.toggle('isActive', chip === button); });
      load(); document.getElementById('restaurant-search').scrollIntoView({ block: 'start' });
    });
  });

  query.value = new URLSearchParams(location.search).get('q') || '';
  load();
}());
