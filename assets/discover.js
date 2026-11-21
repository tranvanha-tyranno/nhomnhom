(function () {
  'use strict';
  const api = window.NhomNhom;
  const host = document.getElementById('discovery-card');
  const message = document.getElementById('discovery-message');
  const query = document.getElementById('discover-query');
  const budget = document.getElementById('discover-budget');
  const spice = document.getElementById('discover-spice');
  const sort = document.getElementById('discover-sort');
  const chips = document.querySelectorAll('[data-vegetarian]');
  const viewButtons = document.querySelectorAll('[data-view-mode]');
  const shortcutHint = document.getElementById('shortcut-hint');

  const state = {
    items: [], index: 0, offset: 0, hasMore: false, vegetarian: '',
    csrf: '', mode: 'walking', view: 'card', saved: new Set(),
    controller: null, busy: false, timer: null
  };

  function setCount() {
    document.getElementById('taste-count').textContent = state.saved.size + ' món đã lưu';
  }

  function renderCardMode() {
    const item = state.items[state.index];
    if (!item) {
      host.innerHTML = '<div class="emptyState collectionEmpty"><strong>Đã xem hết món trong lượt này.</strong><span>Thử đổi bộ lọc hoặc khám phá lại từ đầu nhé.</span><button class="likeButton" data-action="reload">Khám phá lại →</button></div>';
      return;
    }
    const directions = item.isDemo ? '<span class="demoNotice">Địa điểm mẫu · Chưa xác minh</span>'
      : '<a class="dishDirections" href="' + api.escape(api.directions(item, state.mode)) + '" target="_blank" rel="noopener noreferrer">Chỉ đường Google Maps ↗</a>';
    host.innerHTML = '<article class="discoveryDish">'
      + '<div class="discoveryDishTop"><span class="rankBadge">Lựa chọn ' + (state.offset - state.items.length + state.index + 1) + '</span><span class="ratingBadge">★ ' + Number(item.restaurant.rating).toFixed(1) + '</span></div>'
      + '<p class="cuisineLabel">' + api.escape(item.cuisine) + '</p>'
      + '<h2>' + api.escape(item.name) + '</h2>'
      + '<p class="discoveryDescription">' + api.escape(item.description) + '</p>'
      + '<div class="tagRow"><span>' + (item.isVegetarian ? 'Món chay' : 'Món mặn') + '</span><span>' + (item.spiceLevel ? 'Cay cấp ' + item.spiceLevel : 'Không cay') + '</span><span>' + api.money(item.priceVnd) + '</span></div>'
      + '<div class="discoveryRestaurant"><strong><a class="dishRestaurantLink" href="restaurant.php?id=' + Number(item.restaurant.id) + '">' + api.escape(item.restaurant.name) + ' ↗</a></strong><span>' + api.escape(item.restaurant.address) + '</span></div>'
      + '<div class="discoveryActions"><button class="skipButton" data-action="skip">Bỏ qua</button><button class="likeButton" data-action="like">' + (state.saved.has(item.id) ? '♥ Đã thích · Xem tiếp' : '♡ Thích món này') + '</button><a class="bookDishButton" href="bookings.php?restaurant=' + Number(item.restaurant.id) + '">Đặt bàn ↗</a></div>'
      + '<div class="discoveryMapLine">' + directions + '</div></article>';
  }

  function renderGridMode() {
    if (!state.items.length) {
      host.innerHTML = '<div class="emptyState collectionEmpty"><strong>Không tìm thấy món phù hợp.</strong><span>Thử nới lỏng ngân sách hoặc độ cay nhé.</span><button class="likeButton" data-action="reload">Tải lại →</button></div>';
      return;
    }
    const cardsHtml = state.items.map(function (item) {
      const saved = state.saved.has(item.id);
      return '<article class="resultCard discoverGridCard">'
        + '<div class="resultTopline"><span class="cuisineLabel">' + api.escape(item.cuisine) + '</span><span class="ratingBadge">★ ' + Number(item.restaurant.rating).toFixed(1) + '</span></div>'
        + '<div class="resultBody"><h3>' + api.escape(item.name) + '</h3><p>' + api.escape(item.description) + '</p></div>'
        + '<div class="tagRow"><span>' + api.money(item.priceVnd) + '</span><span>' + (item.isVegetarian ? 'Món chay' : 'Món mặn') + '</span><span>' + (item.spiceLevel ? 'Cay ' + item.spiceLevel : 'Không cay') + '</span></div>'
        + '<div class="resultFooter"><a class="addressLine cardTitleLink" href="restaurant.php?id=' + Number(item.restaurant.id) + '">' + api.escape(item.restaurant.name) + ' ↗</a>'
        + '<div class="cardActionRow"><button type="button" class="' + (saved ? 'likeButton' : 'outlineButton') + '" data-grid-like="' + api.escape(item.id) + '" data-grid-name="' + api.escape(item.name) + '">' + (saved ? '♥ Đã thích' : '♡ Thích') + '</button>'
        + '<a class="outlineButton" href="bookings.php?restaurant=' + Number(item.restaurant.id) + '">Đặt bàn</a></div></div></article>';
    }).join('');
    const moreBtn = state.hasMore ? '<div class="gridLoadMore"><button type="button" class="outlineButton" data-action="load-more">Xem thêm món</button></div>' : '';
    host.innerHTML = '<div class="resultsGrid discoverGrid">' + cardsHtml + '</div>' + moreBtn;
  }

  function render() {
    if (shortcutHint) shortcutHint.hidden = state.view !== 'card';
    if (state.view === 'grid') renderGridMode();
    else renderCardMode();
  }

  function playRouletteAnimation(finalCallback) {
    const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) { finalCallback(); return; }
    const samples = ['Phở bò tái chín', 'Bún bò Huế', 'Cơm tấm sườn bì', 'Bánh mì thịt nướng', 'Bún chả Hà Nội'];
    let step = 0;
    host.innerHTML = '<div class="rouletteBox"><span class="rouletteTag">Đang quay món hợp gu…</span><strong class="rouletteText">' + samples[0] + '</strong></div>';
    const interval = setInterval(function () {
      step += 1;
      const el = host.querySelector('.rouletteText');
      if (el) el.textContent = samples[step % samples.length];
      if (step >= 5) {
        clearInterval(interval);
        finalCallback();
      }
    }, 85);
  }

  async function load(reset, surprise) {
    if (state.controller) state.controller.abort();
    const controller = new AbortController(); state.controller = controller;
    if (reset) state.offset = 0;
    host.setAttribute('aria-busy', 'true');
    if (!surprise) host.innerHTML = api.skeletonCards(state.view === 'grid' ? 4 : 1);
    const params = new URLSearchParams({ q: query.value, limit: state.view === 'grid' ? '8' : '8', offset: String(state.offset), maxPrice: budget.value, maxSpice: spice.value, sort: sort.value });
    if (surprise) { params.set('sort', 'random'); params.set('limit', '1'); params.set('offset', '0'); state.view = 'card'; syncViewButtons(); }
    if (state.vegetarian) params.set('vegetarian', state.vegetarian);
    try {
      const body = await api.request('api/discover.php?' + params, { signal: controller.signal });
      const applyData = function () {
        state.items = (!reset && state.view === 'grid') ? state.items.concat(body.data) : body.data;
        state.index = 0;
        state.offset += body.data.length;
        state.hasMore = surprise ? false : body.meta.hasMore;
        document.getElementById('discovery-total').textContent = Number(body.meta.total).toLocaleString('vi-VN') + ' lựa chọn phù hợp';
        render();
        if (surprise && body.data.length) {
          message.textContent = 'Món được chọn ngẫu nhiên theo bộ lọc của bạn. Thích thì lưu, hoặc quay lại nhé!';
          api.toast('Gợi ý cho bạn: ' + body.data[0].name, 'success');
        }
      };
      if (surprise && body.data.length) playRouletteAnimation(applyData);
      else applyData();
    } catch (error) {
      if (error.name === 'AbortError') return;
      document.getElementById('discovery-total').textContent = 'Không tải được món';
      host.innerHTML = '<div class="emptyState" role="alert">' + api.escape(error.message) + '<button class="quickQuery" data-action="reload">Thử lại</button></div>';
    } finally { if (state.controller === controller) host.setAttribute('aria-busy', 'false'); }
  }

  async function vote(action) {
    const item = state.items[state.index];
    if (!item || state.busy) return;
    state.busy = true;
    host.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
    try {
      if (action === 'like' && !state.saved.has(item.id)) {
        await api.post('api/taste.php', { dishId: Number(item.id), action: 'like' }, state.csrf);
        state.saved.add(item.id); setCount();
        message.textContent = 'Đã thêm “' + item.name + '” vào bộ sưu tập.';
        api.toast('Đã lưu “' + item.name + '”!', 'success');
      }
      state.index += 1;
      if (state.index >= state.items.length && state.hasMore) await load(false); else render();
    } catch (error) { message.textContent = error.message; api.toast(error.message, 'error'); }
    finally { state.busy = false; host.querySelectorAll('button').forEach(function (button) { button.disabled = false; }); }
  }

  async function toggleGridLike(dishId, dishName, btn) {
    if (state.busy || !state.csrf) return;
    state.busy = true; btn.disabled = true;
    try {
      const already = state.saved.has(dishId);
      await api.post('api/taste.php', { dishId: Number(dishId), action: already ? 'remove' : 'like' }, state.csrf);
      if (already) { state.saved.delete(dishId); api.toast('Đã bỏ lưu “' + dishName + '”.', 'info'); }
      else { state.saved.add(dishId); api.toast('Đã lưu “' + dishName + '”!', 'success'); }
      setCount(); render();
    } catch (error) { api.toast(error.message, 'error'); }
    finally { state.busy = false; }
  }

  function updateChips() {
    chips.forEach(function (chip) {
      const active = chip.dataset.vegetarian === state.vegetarian;
      chip.classList.toggle('isActive', active);
      chip.setAttribute('aria-pressed', String(active));
    });
  }
  function syncViewButtons() {
    viewButtons.forEach(function (btn) {
      const active = btn.dataset.viewMode === state.view;
      btn.classList.toggle('isActive', active);
      btn.setAttribute('aria-pressed', String(active));
    });
  }
  function refresh() { message.textContent = ''; load(true); }

  chips.forEach(function (chip) { chip.addEventListener('click', function () { state.vegetarian = chip.dataset.vegetarian; updateChips(); refresh(); }); });
  viewButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (state.view === btn.dataset.viewMode) return;
      state.view = btn.dataset.viewMode;
      syncViewButtons();
      render();
    });
  });
  [budget, spice, sort].forEach(function (input) { input.addEventListener('change', refresh); });
  query.addEventListener('input', function () { clearTimeout(state.timer); state.timer = setTimeout(refresh, 350); });
  document.getElementById('reset-filters').addEventListener('click', function () {
    clearTimeout(state.timer); query.value = ''; budget.value = '0'; spice.value = '3'; sort.value = 'rating'; state.vegetarian = ''; updateChips(); refresh();
  });
  host.addEventListener('click', function (event) {
    const gridBtn = event.target.closest('[data-grid-like]');
    if (gridBtn) { toggleGridLike(gridBtn.dataset.gridLike, gridBtn.dataset.gridName, gridBtn); return; }
    const button = event.target.closest('[data-action]');
    if (!button) return;
    if (button.dataset.action === 'reload') load(true);
    else if (button.dataset.action === 'load-more') load(false);
    else vote(button.dataset.action);
  });
  document.getElementById('surprise-dish').addEventListener('click', function () {
    if (state.busy) return; clearTimeout(state.timer); message.textContent = ''; load(true, true);
  });
  document.addEventListener('keydown', function (event) {
    if (state.view !== 'card' || state.busy) return;
    const tag = (event.target && event.target.tagName) || '';
    if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;
    if (event.key === 'ArrowLeft') { event.preventDefault(); vote('skip'); }
    else if (event.key === 'ArrowRight') { event.preventDefault(); vote('like'); }
    else if (event.key === ' ') { event.preventDefault(); load(true, true); }
  });

  async function start() {
    try {
      const results = await Promise.all([api.request('api/profile.php'), api.request('api/taste.php')]);
      const profile = results[0].data; state.csrf = results[0].csrfToken; state.mode = profile.travelMode;
      if (profile.maxPriceVnd > 0 && !Array.from(budget.options).some(function (option) { return Number(option.value) === profile.maxPriceVnd; })) {
        budget.add(new Option('Đến ' + api.money(profile.maxPriceVnd), String(profile.maxPriceVnd)));
      }
      budget.value = String(profile.maxPriceVnd); state.vegetarian = profile.vegetarianOnly ? '1' : ''; updateChips();
      state.saved = new Set(results[1].data.map(function (item) { return item.id; })); setCount();
    } catch (error) { message.textContent = error.message + ' Bạn vẫn có thể xem món và thử tải lại trang để lưu.'; }
    await load(true, new URLSearchParams(location.search).get('surprise') === '1');
  }
  start();
}());
