(function () {
  'use strict';
  const api = window.NhomNhom;
  let csrfToken = '';

  function load() {
    return api.request('api/profile.php', { headers: { Accept: 'application/json' } }).then(function (body) {
      csrfToken = body.csrfToken;
      document.getElementById('profile-name').value = body.data.displayName;
      document.getElementById('profile-budget').value = body.data.maxPriceVnd || '';
      document.getElementById('profile-vegetarian').checked = body.data.vegetarianOnly;
      document.getElementById('profile-mode').value = body.data.travelMode;
    });
  }

  function loadStats() {
    const favEl = document.getElementById('stat-favorites');
    const bookEl = document.getElementById('stat-bookings');
    if (favEl) {
      api.request('api/taste.php').then(function (body) {
        favEl.textContent = String((body.data || []).length);
      }).catch(function () { favEl.textContent = '0'; });
    }
    if (bookEl) {
      api.request('api/bookings.php').then(function (body) {
        const upcoming = (body.data || []).filter(function (b) { return b.status !== 'cancelled'; }).length;
        bookEl.textContent = String(upcoming);
      }).catch(function () { bookEl.textContent = '0'; });
    }
  }

  document.getElementById('profile-form').addEventListener('submit', function (event) {
    event.preventDefault();
    const message = document.getElementById('profile-message');
    api.post('api/profile.php', {
      displayName: document.getElementById('profile-name').value,
      maxPriceVnd: Number(document.getElementById('profile-budget').value || 0),
      vegetarianOnly: document.getElementById('profile-vegetarian').checked,
      travelMode: document.getElementById('profile-mode').value
    }, csrfToken).then(function () {
      message.textContent = 'Đã lưu thay đổi.';
      api.toast('Đã lưu hồ sơ khẩu vị!', 'success');
    }).catch(function (error) {
      message.textContent = error.message;
      api.toast(error.message, 'error');
    });
  });

  load().catch(function (error) {
    document.getElementById('profile-message').textContent = error.message + ' Hãy tải lại trang để thử lại.';
  });
  loadStats();
}());
