(function () {
  'use strict';
  const toggle = document.querySelector('.menuToggle');
  function closeMenu() {
    if (!toggle) return;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Mở điều hướng');
    document.body.classList.remove('menuOpen');
  }
  if (toggle) {
    toggle.addEventListener('click', function () {
      const expanded = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(expanded));
      toggle.setAttribute('aria-label', expanded ? 'Đóng điều hướng' : 'Mở điều hướng');
      document.body.classList.toggle('menuOpen', expanded);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && document.body.classList.contains('menuOpen')) {
        closeMenu();
        toggle.focus();
      }
    });
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.siteSidebar, .menuToggle')) closeMenu();
    });
    window.matchMedia('(min-width: 1001px)').addEventListener('change', closeMenu);
  }

  const themeBtn = document.getElementById('theme-toggle');
  if (themeBtn) {
    function syncThemeButton() {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      themeBtn.setAttribute('aria-pressed', String(isDark));
      themeBtn.setAttribute('aria-label', isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối');
      const meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.setAttribute('content', isDark ? '#0e1b17' : '#174b3c');
    }
    syncThemeButton();
    themeBtn.addEventListener('click', function () {
      const nextDark = document.documentElement.getAttribute('data-theme') !== 'dark';
      if (nextDark) document.documentElement.setAttribute('data-theme', 'dark');
      else document.documentElement.removeAttribute('data-theme');
      try { localStorage.setItem('nhomnhom-theme', nextDark ? 'dark' : 'light'); } catch (e) { /* ignore */ }
      syncThemeButton();
      if (window.NhomNhom && window.NhomNhom.toast) {
        window.NhomNhom.toast(nextDark ? 'Đã bật chế độ tối' : 'Đã bật chế độ sáng', 'info');
      }
    });
  }

  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = document.getElementById(button.getAttribute('aria-controls'));
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.textContent = show ? 'Ẩn' : 'Hiện';
      button.setAttribute('aria-pressed', String(show));
    });
  });
}());
