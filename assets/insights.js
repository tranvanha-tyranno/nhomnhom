(function () {
  'use strict';
  const api = window.NhomNhom;
  const list = document.getElementById('insights-list');
  const dishHost = document.getElementById('top-dishes-list');

  api.request('api/insights.php').then(function (body) {
    const places = (body.data || []).slice(0, 12);
    if (!places.length) {
      list.innerHTML = '<p class="emptyState">Chưa có dữ liệu xu hướng.</p>';
    } else {
      const maxScore = Math.max(1, places[0].score || 1);
      list.innerHTML = places.map(function (item, index) {
        const pct = Math.max(8, Math.round(((item.score || 0) / maxScore) * 100));
        return '<div class="insightRow">'
          + '<span class="rankBadge">#' + (index + 1) + '</span>'
          + '<div class="insightMeta">'
          + '<strong><a class="cardTitleLink" href="restaurant.php?id=' + Number(item.id) + '">' + api.escape(item.name) + '</a></strong>'
          + '<small>' + api.escape(item.address) + ' · ' + Number(item.likes) + ' lượt thích · ' + Number(item.bookings) + ' đặt bàn</small>'
          + '<div class="scoreBarTrack" aria-hidden="true"><div class="scoreBarFill" style="width:' + pct + '%"></div></div>'
          + '</div>'
          + '<b>' + Number(item.score) + ' điểm</b></div>';
      }).join('');
    }

    const dishes = body.topDishes || [];
    if (dishHost) {
      if (!dishes.length) {
        dishHost.innerHTML = '<p class="emptyState">Chưa có bảng xếp hạng món ăn.</p>';
      } else {
        dishHost.innerHTML = dishes.map(function (dish, index) {
          return '<div class="topDishRow">'
            + '<span class="rankBadge">#' + (index + 1) + '</span>'
            + '<div class="insightMeta">'
            + '<strong><a class="cardTitleLink" href="index.php?q=' + encodeURIComponent(dish.name) + '">' + api.escape(dish.name) + '</a></strong>'
            + '<small>' + api.escape(dish.cuisine) + ' · Từ ' + api.money(dish.minPriceVnd) + ' · ' + Number(dish.restaurantCount) + ' quán</small>'
            + '</div>'
            + '<span class="eloPill">Elo ' + Number(dish.eloRating) + '</span>'
            + '</div>';
        }).join('');
      }
    }
  }).catch(function (error) {
    list.innerHTML = '<p class="emptyState" role="alert">' + api.escape(error.message) + '</p>';
    if (dishHost) dishHost.innerHTML = '<p class="emptyState" role="alert">' + api.escape(error.message) + '</p>';
  });
}());
