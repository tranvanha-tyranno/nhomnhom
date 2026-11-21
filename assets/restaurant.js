(function () {
  'use strict';
  const api = window.NhomNhom;
  const hero = document.querySelector('.detailHero');
  if (!hero) return;
  const state = { csrf: '', saved: new Set() };

  const modeSelect = document.getElementById('detail-travel-mode');
  const mapLink = document.getElementById('detail-map-link');
  if (modeSelect && mapLink) {
    modeSelect.addEventListener('change', function () {
      const url = new URL(mapLink.href);
      url.searchParams.set('travelmode', modeSelect.value);
      mapLink.href = url.toString();
    });
  }

  const shareBtn = document.getElementById('share-restaurant');
  if (shareBtn) {
    shareBtn.addEventListener('click', async function () {
      try {
        await navigator.clipboard.writeText(window.location.href);
        api.toast('Đã sao chép liên kết quán ăn!', 'success');
      } catch (e) {
        api.toast('Không thể tự động sao chép, bạn có thể chép trực tiếp từ thanh địa chỉ.', 'info');
      }
    });
  }

  const filterChips = document.querySelectorAll('[data-menu-filter]');
  const cards = document.querySelectorAll('.menuItemCard');
  filterChips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      const filter = chip.dataset.menuFilter;
      filterChips.forEach(function (btn) {
        const active = btn === chip;
        btn.classList.toggle('isActive', active);
        btn.setAttribute('aria-pressed', String(active));
      });
      cards.forEach(function (card) {
        const show = filter === 'all'
          || (filter === 'veg' && card.dataset.veg === '1')
          || (filter === 'mild' && card.dataset.spice === '0');
        card.hidden = !show;
      });
    });
  });

  function syncSavedButtons() {
    document.querySelectorAll('.saveDishBtn').forEach(function (btn) {
      const isSaved = state.saved.has(btn.dataset.dishId);
      btn.classList.toggle('isSaved', isSaved);
      btn.textContent = isSaved ? '♥ Đã lưu vào yêu thích' : '♡ Lưu món này';
    });
  }

  document.addEventListener('click', async function (event) {
    const btn = event.target.closest('.saveDishBtn');
    if (!btn || btn.disabled) return;
    const dishId = btn.dataset.dishId;
    const dishName = btn.dataset.dishName || 'món này';
    if (!state.csrf) {
      api.toast('Đang khởi tạo phiên, vui lòng thử lại sau giây lát.', 'info');
      return;
    }
    btn.disabled = true;
    try {
      const already = state.saved.has(dishId);
      await api.post('api/taste.php', { dishId: Number(dishId), action: already ? 'remove' : 'like' }, state.csrf);
      if (already) {
        state.saved.delete(dishId);
        api.toast('Đã bỏ lưu “' + dishName + '”.', 'info');
      } else {
        state.saved.add(dishId);
        api.toast('Đã lưu “' + dishName + '” vào bộ sưu tập!', 'success');
      }
      syncSavedButtons();
    } catch (error) {
      api.toast(error.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  Promise.all([api.request('api/profile.php'), api.request('api/taste.php')]).then(function (results) {
    state.csrf = results[0].csrfToken;
    if (modeSelect && results[0].data && results[0].data.travelMode) {
      modeSelect.value = results[0].data.travelMode;
      modeSelect.dispatchEvent(new Event('change'));
    }
    state.saved = new Set((results[1].data || []).map(function (item) { return String(item.id); }));
    syncSavedButtons();
  }).catch(function () { /* guest fallback */ });
}());
