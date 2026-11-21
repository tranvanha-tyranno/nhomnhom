(function () {
  'use strict';
  const api = window.NhomNhom;
  const list = document.getElementById('favorite-list');
  const query = document.getElementById('favorite-query');
  const sort = document.getElementById('favorite-sort');
  const message = document.getElementById('favorite-message');
  let items = [];
  let csrfToken = '';
  const normalize = function (text) { return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').toLowerCase(); };
  function card(item) {
    const directions = item.isDemo ? '<span class="demoNotice">Địa điểm mẫu · Chưa xác minh</span>'
      : '<a class="mapButton" href="' + api.escape(api.directions(item)) + '" target="_blank" rel="noopener noreferrer">Chỉ đường ↗</a>';
    return '<article class="resultCard favoriteCard"><div class="resultTopline"><span class="favoriteEmblem">♡</span><button class="removeFavorite" data-remove="' + Number(item.id) + '" aria-label="Bỏ lưu ' + api.escape(item.name) + '">Bỏ lưu ×</button></div><div class="resultBody"><p class="cuisineLabel">' + api.escape(item.cuisine) + '</p><h3>' + api.escape(item.name) + '</h3><p>' + api.escape(item.description) + '</p></div><div class="tagRow"><span>' + (item.isVegetarian ? 'Món chay' : 'Món mặn') + '</span><span>' + (item.spiceLevel ? 'Cay cấp ' + item.spiceLevel : 'Không cay') + '</span></div><strong class="favoritePrice">Từ ' + api.money(item.priceVnd) + '</strong><div class="resultFooter"><strong>' + api.escape(item.restaurant.name) + '</strong><span class="addressLine">' + api.escape(item.restaurant.address) + '</span>' + directions + '<a class="outlineButton" href="bookings.php?restaurant=' + Number(item.restaurant.id) + '">Đặt bàn tại đây →</a></div></article>';
  }
  function render() {
    const filtered = items.filter(function (item) { return normalize(item.name + ' ' + item.cuisine).includes(normalize(query.value)); });
    if (sort.value === 'price') filtered.sort(function (a, b) { return a.priceVnd - b.priceVnd; });
    if (sort.value === 'name') filtered.sort(function (a, b) { return a.name.localeCompare(b.name, 'vi'); });
    document.getElementById('favorite-count').textContent = filtered.length + ' / ' + items.length + ' món';
    list.innerHTML = filtered.length ? filtered.map(card).join('') : '<div class="emptyState collectionEmpty"><span class="favoriteEmblem">♡</span><strong>' + (items.length ? 'Chưa tìm thấy món này.' : 'Bộ sưu tập đang chờ món đầu tiên.') + '</strong><span>' + (items.length ? 'Thử một từ khóa khác nhé.' : 'Thích một món ở trang Khám phá, món sẽ xuất hiện tại đây.') + '</span><a class="likeButton" href="discover.php">Tìm món hợp gu →</a></div>';
  }
  async function load() {
    list.setAttribute('aria-busy', 'true');
    try {
      const body = await api.request('api/taste.php'); items = body.data; csrfToken = body.csrfToken; render();
    } catch (error) {
      list.innerHTML = '<div class="emptyState" role="alert">' + api.escape(error.message) + '<button class="quickQuery" data-retry>Thử lại</button></div>';
    } finally { list.setAttribute('aria-busy', 'false'); }
  }
  list.addEventListener('click', async function (event) {
    if (event.target.closest('[data-retry]')) { load(); return; }
    const button = event.target.closest('[data-remove]');
    if (!button) return;
    button.disabled = true;
    try {
      await api.post('api/taste.php', { action: 'remove', dishId: Number(button.dataset.remove) }, csrfToken);
      items = items.filter(function (item) { return item.id !== button.dataset.remove; });
      message.textContent = 'Đã bỏ lưu món. Bạn có thể thích lại ở trang Khám phá.';
      render(); query.focus();
    } catch (error) { message.textContent = error.message; button.disabled = false; }
  });
  query.addEventListener('input', render); sort.addEventListener('change', render); load();
}());
