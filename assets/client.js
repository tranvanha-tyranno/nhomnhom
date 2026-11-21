(function () {
  'use strict';
  let toastTimer = null;
  window.NhomNhom = {
    escape: function (value) {
      return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
      });
    },
    request: async function (url, options) {
      const response = await fetch(url, options);
      const body = await response.json();
      if (!response.ok || body.success !== true) throw new Error(body.error || 'Không thể tải dữ liệu. Vui lòng thử lại.');
      return body;
    },
    post: function (url, payload, csrfToken) {
      return this.request(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(Object.assign({}, payload, { csrfToken: csrfToken }))
      });
    },
    money: function (amount) { return Number(amount).toLocaleString('vi-VN') + ' đ'; },
    priceTier: function (level) {
      const n = Math.max(1, Math.min(3, Number(level) || 1));
      return '₫'.repeat(n);
    },
    distanceKm: function (lat1, lng1, lat2, lng2) {
      const toRad = function (deg) { return (deg * Math.PI) / 180; };
      const dLat = toRad(lat2 - lat1);
      const dLng = toRad(lng2 - lng1);
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
      return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    },
    directions: function (item, mode, origin) {
      const loc = item.restaurant || item;
      const params = new URLSearchParams({ api: '1', destination: loc.lat + ',' + loc.lng, travelmode: mode || 'walking' });
      if (origin && Number.isFinite(origin.lat) && Number.isFinite(origin.lng)) {
        params.set('origin', origin.lat.toFixed(6) + ',' + origin.lng.toFixed(6));
      }
      return 'https://www.google.com/maps/dir/?' + params.toString();
    },
    toast: function (message, type) {
      const region = document.getElementById('toast-region');
      if (!region || !message) return;
      clearTimeout(toastTimer);
      region.innerHTML = '<div class="toastItem toast-' + this.escape(type || 'info') + '">' + this.escape(message) + '</div>';
      region.classList.add('isVisible');
      toastTimer = setTimeout(function () { region.classList.remove('isVisible'); }, 3200);
    },
    skeletonCards: function (count) {
      let html = '<div class="resultsGrid" aria-hidden="true">';
      for (let i = 0; i < (count || 6); i += 1) {
        html += '<div class="skeletonCard"><div class="skLine skShort"></div><div class="skLine skTitle"></div><div class="skLine"></div><div class="skLine skMid"></div><div class="skFooter"></div></div>';
      }
      return html + '</div>';
    }
  };
}());
