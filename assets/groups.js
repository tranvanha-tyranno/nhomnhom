(function () {
  'use strict';
  const api = window.NhomNhom;
  const message = document.getElementById('group-message');
  const dishes = document.getElementById('group-dishes');
  const query = document.getElementById('group-dish-query');
  const more = document.getElementById('more-group-dishes');
  const state = { csrf: '', code: '', offset: 0, controller: null, timer: null, busy: false, votes: new Map() };
  function show(data) {
    document.getElementById('group-code').value = data.inviteCode;
    message.textContent = 'Mã nhóm: ' + data.inviteCode + ' · ' + Number(data.memberCount) + ' thành viên';
    document.getElementById('group-result').innerHTML = (data.matches || []).map(function (item) {
      return '<div class="groupMatch"><strong>' + api.escape(item.name) + '</strong><span>' + Number(item.likes) + '/' + Number(data.memberCount) + ' thích</span></div>';
    }).join('') || '<p class="emptyState">Chưa có bình chọn. Chọn món ở bên dưới để bắt đầu.</p>';
  }
  async function refresh() {
    const body = await api.request('api/groups.php?' + new URLSearchParams({ inviteCode: state.code }));
    show(body.data);
  }
  async function loadDishes(reset) {
    if (state.controller) state.controller.abort();
    const controller = new AbortController(); state.controller = controller;
    if (reset) state.offset = 0;
    dishes.setAttribute('aria-busy', 'true');
    more.disabled = true;
    try {
      const body = await api.request('api/group-dishes.php?' + new URLSearchParams({ q: query.value, limit: '9', offset: String(state.offset) }), { signal: controller.signal });
      const unique = Array.from(new Map(body.data.map(function (item) { return [item.id, item]; })).values());
      dishes.innerHTML = unique.map(function (item) {
        const vote = state.votes.get(item.id);
        return '<article class="resultCard"><div class="resultBody"><p class="cuisineLabel">' + api.escape(item.cuisine) + '</p><h3>' + api.escape(item.name) + '</h3><p>' + api.escape(item.description) + '</p></div><strong class="favoritePrice">' + api.money(item.priceVnd) + '</strong><div class="groupVoteActions"><button class="filterChip' + (vote === 'like' ? ' isActive' : '') + '" data-dish="' + Number(item.id) + '" data-vote="like" aria-pressed="' + (vote === 'like') + '">♡ Muốn ăn</button><button class="filterChip' + (vote === 'skip' ? ' isActive' : '') + '" data-dish="' + Number(item.id) + '" data-vote="skip" aria-pressed="' + (vote === 'skip') + '">Để lần sau</button></div><a class="textLink" href="index.php?q=' + encodeURIComponent(item.name) + '#restaurant-search">Tìm quán có món này</a></article>';
      }).join('') || '<p class="emptyState">Chưa tìm thấy món. Thử từ khóa khác nhé.</p>';
      state.offset += body.data.length; more.hidden = !body.meta.hasMore;
    } catch (error) {
      if (error.name !== 'AbortError') { dishes.innerHTML = '<p class="emptyState" role="alert">' + api.escape(error.message) + '</p>'; more.hidden = false; }
    } finally {
      if (state.controller === controller) { dishes.setAttribute('aria-busy', 'false'); more.disabled = false; }
    }
  }
  async function enter(action) {
    if (state.busy || !state.csrf) return;
    state.busy = true;
    const buttons = document.querySelectorAll('#create-group, #join-group');
    buttons.forEach(function (button) { button.disabled = true; });
    try {
      const body = await api.post('api/groups.php', { action: action, inviteCode: document.getElementById('group-code').value.trim(), displayName: document.getElementById('group-name').value.trim() || 'Bạn' }, state.csrf);
      if (state.code !== body.data.inviteCode) state.votes.clear();
      state.code = body.data.inviteCode;
      document.getElementById('group-voting').hidden = false;
      await Promise.all([refresh(), loadDishes(true)]);
    } catch (error) { message.textContent = error.message; }
    finally { state.busy = false; buttons.forEach(function (button) { button.disabled = false; }); }
  }
  document.getElementById('create-group').addEventListener('click', function () { enter('create'); });
  document.getElementById('join-group').addEventListener('click', function () { enter('join'); });
  document.getElementById('refresh-group').addEventListener('click', function () { refresh().catch(function (error) { message.textContent = error.message; }); });
  document.getElementById('copy-group').addEventListener('click', async function () {
    try { await navigator.clipboard.writeText(state.code); message.textContent = 'Đã sao chép mã nhóm: ' + state.code; }
    catch (error) { message.textContent = 'Không thể sao chép tự động. Hãy sao chép mã trong ô Mã nhóm.'; document.getElementById('group-code').select(); }
  });
  dishes.addEventListener('click', async function (event) {
    const button = event.target.closest('[data-vote]'); if (!button || state.busy) return;
    state.busy = true; dishes.querySelectorAll('button').forEach(function (item) { item.disabled = true; });
    try {
      const body = await api.post('api/groups.php', { action: 'vote', inviteCode: state.code, dishId: Number(button.dataset.dish), vote: button.dataset.vote }, state.csrf);
      state.votes.set(button.dataset.dish, button.dataset.vote); show(body.data);
      dishes.querySelectorAll('[data-dish="' + button.dataset.dish + '"]').forEach(function (item) {
        const selected = item === button; item.classList.toggle('isActive', selected); item.setAttribute('aria-pressed', String(selected));
      });
    } catch (error) { message.textContent = error.message; }
    finally { state.busy = false; dishes.querySelectorAll('button').forEach(function (item) { item.disabled = false; }); }
  });
  query.addEventListener('input', function () { clearTimeout(state.timer); state.timer = setTimeout(function () { loadDishes(true); }, 300); });
  more.addEventListener('click', function () { loadDishes(false); });
  api.request('api/profile.php').then(function (body) {
    state.csrf = body.csrfToken; document.getElementById('group-name').value = body.data.displayName;
  }).catch(function (error) { message.textContent = error.message + ' Hãy tải lại trang để thử lại.'; });
}());
