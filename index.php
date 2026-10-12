<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Tìm món ăn yêu thích và mở chỉ đường Google Maps.">
  <title>NhomNhom — Tìm món ăn</title>
  <link rel="preload" href="public/fonts/noto-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="public/fonts/noto-sans-vietnamese.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/app.css">
  <link rel="stylesheet" href="assets/explore.css">
  <link rel="stylesheet" href="assets/tabs.css">
  <link rel="stylesheet" href="assets/typography.css">
</head>
<body>
  <main class="appShell">
    <header class="heroBand" aria-labelledby="app-title">
      <div class="heroContent">
        <div class="brandBlock">
          <h1 id="app-title"><span aria-hidden="true">🍜</span> NhomNhom</h1>
          <p>Tìm món hợp gu, chọn quán vừa ý.</p>
        </div>
      </div>
    </header>
    <nav class="tabNavigation" aria-label="Các tính năng NhomNhom">
      <div class="appTabs" id="app-tabs" role="tablist" aria-label="Chọn tính năng">
        <a id="tab-search" class="appTab" role="tab" href="#search" data-open-tab="search" aria-controls="panel-search" aria-selected="true">Tìm quán</a>
        <a id="tab-discovery" class="appTab" role="tab" href="#discovery" data-open-tab="discovery" aria-controls="panel-discovery" aria-selected="false" tabindex="-1">Khám phá món</a>
        <a id="tab-saved" class="appTab" role="tab" href="#saved" data-open-tab="saved" aria-controls="panel-saved" aria-selected="false" tabindex="-1">Quán đã lưu</a>
        <a id="tab-profile" class="appTab" role="tab" href="#profile" data-open-tab="profile" aria-controls="panel-profile" aria-selected="false" tabindex="-1">Khẩu vị</a>
        <a id="tab-groups" class="appTab" role="tab" href="#groups" data-open-tab="groups" aria-controls="panel-groups" aria-selected="false" tabindex="-1">Nhóm</a>
        <a id="tab-booking" class="appTab" role="tab" href="#booking" data-open-tab="booking" aria-controls="panel-booking" aria-selected="false" tabindex="-1">Đặt bàn</a>
        <a id="tab-insights" class="appTab" role="tab" href="#insights" data-open-tab="insights" aria-controls="panel-insights" aria-selected="false" tabindex="-1">Xu hướng</a>
      </div>
    </nav>
    <noscript><p class="tabNotice">Vui lòng bật JavaScript để chuyển tab và sử dụng các tính năng.</p></noscript>
    <div class="tabWorkspace">
      <div id="explore-tools" class="exploreTools">
        <div class="compareBar"><button class="quickQuery" type="button" id="compare-open" disabled>So sánh (0/3)</button><div id="compare-selection" aria-label="Quán được chọn so sánh"></div></div>
        <p id="explore-message" class="exploreMessage" role="status"></p>
      </div>
      <section id="panel-search" class="tabPanel" role="tabpanel" aria-labelledby="tab-search" tabindex="0">
        <div class="panelIntro"><span class="sectionEyebrow">Tìm & so sánh</span><h2>Tìm quán hợp ý</h2><p>Tìm món bạn thích, lọc theo ngân sách rồi so sánh các quán.</p></div>
        <form class="searchPanel" id="search-form" aria-label="Tìm món ăn">
          <label class="searchBox">
            <span aria-hidden="true">⌕</span>
            <span class="srOnly">Tìm món ăn</span>
            <input id="query" name="q" maxlength="80" type="search" autocomplete="off" placeholder="Bạn muốn ăn gì hôm nay?">
          </label>
          <div class="quickQueryRow" aria-label="Gợi ý món ăn">
            <button class="quickQuery" type="button" data-query="bún bò">bún bò</button>
            <button class="quickQuery" type="button" data-query="phở">phở</button>
            <button class="quickQuery" type="button" data-query="chay">chay</button>
            <button class="quickQuery" type="button" data-query="gỏi cuốn">gỏi cuốn</button>
          </div>
          <div class="modeSelector" role="group" aria-label="Chọn cách di chuyển">
            <button class="modeButton isActive" aria-pressed="true" data-mode="driving" type="button">🚗 <span>Xe</span></button>
            <button class="modeButton" aria-pressed="false" data-mode="walking" type="button">🚶 <span>Đi bộ</span></button>
            <button class="modeButton" aria-pressed="false" data-mode="bicycling" type="button">🚲 <span>Xe đạp</span></button>
            <button class="modeButton" aria-pressed="false" data-mode="transit" type="button">🚌 <span>Transit</span></button>
          </div>
        </form>
        <form id="restaurant-filters" class="restaurantFilters" aria-label="Lọc quán ăn">
          <label>Giá món tối đa (đ)<input name="maxPrice" type="number" min="0" max="100000000" step="1000" placeholder="Không giới hạn"></label>
          <label>Phân khúc quán<select name="priceLevel"><option value="0">Tất cả mức giá</option><option value="1">Bình dân</option><option value="2">Vừa phải</option><option value="3">Cao cấp</option><option value="4">Sang trọng</option></select></label>
          <label>Đánh giá tối thiểu<select name="minRating"><option value="0">Tất cả đánh giá</option><option value="3">3 sao trở lên</option><option value="4">4 sao trở lên</option><option value="4.5">4,5 sao trở lên</option></select></label>
          <label>Sắp xếp<select name="sort"><option value="relevance">Phù hợp nhất</option><option value="rating">Đánh giá cao nhất</option><option value="price_asc">Giá món thấp nhất</option><option value="reviews">Nhiều đánh giá nhất</option></select></label>
          <label class="checkLine"><input name="vegetarian" type="checkbox" value="1"> Có món chay</label>
          <div class="filterActions"><button class="likeButton" type="submit">Áp dụng bộ lọc</button><button class="quickQuery" type="button" id="reset-filters">Xóa bộ lọc</button><button class="quickQuery" type="button" id="random-restaurant">🎲 Hôm nay ăn đâu?</button></div>
        </form>
        <p class="filterHint">Lọc theo món đang bán trong ngân sách. Khi chọn món chay, quán phải có món chay đáp ứng mức giá này.</p>
        <div class="resultsHeader">
          <h3 id="results-title">Quán phù hợp</h3>
          <div class="resultCount" id="result-count" aria-live="polite">📍 <span>Đang tải…</span></div>
        </div>
        <p class="demoNotice">Bản demo sử dụng quán ăn, địa chỉ và đánh giá giả lập. Các địa điểm này không dùng để chỉ đường thực tế.</p>
        <div id="results" aria-live="polite"><p class="emptyState">Đang tìm quán ăn…</p></div>
      </section>
      <section id="panel-discovery" class="tabPanel" role="tabpanel" aria-labelledby="tab-discovery" tabindex="0" hidden>
      <div class="discoveryContent">
        <div class="discoveryHeader">
          <div>
            <span class="sectionEyebrow">Khám phá theo khẩu vị</span>
            <h2 id="discovery-title">Vuốt chọn món trong 10 giây</h2>
            <p class="discoveryIntro">Thích món hợp gu để NomNom ghi nhớ lựa chọn trên thiết bị này.</p>
          </div>
          <div class="tasteCount" id="taste-count" aria-live="polite">Đã thích 0 món</div>
        </div>
        <div id="discovery-card" class="discoveryCard" aria-live="polite">
          <p class="emptyState">Đang tải món gợi ý…</p>
        </div>
      </div>
      </section>
      <section id="panel-saved" class="tabPanel" role="tabpanel" aria-labelledby="tab-saved" tabindex="0" hidden>
        <div class="panelIntro"><span class="sectionEyebrow">Góc riêng của bạn</span><h2>Quán đã lưu (<span id="saved-count">0</span>)</h2><p>Xem lại quán yêu thích hoặc chọn quán để so sánh.</p></div>
        <div id="saved-panel" class="savedPanel"><p class="filterHint">Danh sách gắn với phiên trên trình duyệt này; xóa cookie hoặc hết phiên có thể làm mất quyền truy cập danh sách.</p><div id="saved-list" aria-live="polite">Đang tải quán đã lưu…</div></div>
        <a class="quickQuery tabShortcut" href="#search" data-open-tab="search">Tìm thêm quán để lưu</a>
      </section>
      <section id="panel-profile" class="tabPanel narrowPanel" role="tabpanel" aria-labelledby="tab-profile" tabindex="0" hidden>
        <div class="panelIntro"><span class="sectionEyebrow">Gu ăn uống</span><h2>Khẩu vị của bạn</h2><p>Lưu sở thích ăn uống và cách di chuyển của bạn.</p></div>
          <form class="featurePanel" id="profile-form">
            <h3>Hồ sơ khẩu vị</h3>
            <label>Tên hiển thị<input id="profile-name" name="displayName" maxlength="80" required></label>
            <label>Ngân sách tối đa<input id="profile-budget" name="maxPriceVnd" type="number" min="0" step="1000" placeholder="0 = không giới hạn"></label>
            <label class="checkLine"><input id="profile-vegetarian" name="vegetarianOnly" type="checkbox"> Chỉ gợi ý món chay</label>
            <label>Phương tiện<select id="profile-mode" name="travelMode"><option value="walking">Đi bộ</option><option value="driving">Xe</option><option value="bicycling">Xe đạp</option><option value="transit">Transit</option></select></label>
            <button class="likeButton" type="submit">Lưu khẩu vị</button><p class="formMessage" id="profile-message" role="status"></p>
          </form>
      </section>
      <section id="panel-groups" class="tabPanel narrowPanel" role="tabpanel" aria-labelledby="tab-groups" tabindex="0" hidden>
        <div class="panelIntro"><span class="sectionEyebrow">Đi ăn cùng nhau</span><h2>Nhóm của bạn</h2><p>Tạo nhóm mới hoặc nhập mã để tham gia cùng bạn bè.</p></div>
          <div class="featurePanel">
            <h3>Chọn món theo nhóm</h3>
            <div class="inlineForm"><input id="group-name" maxlength="80" placeholder="Tên của bạn"><button class="quickQuery" id="create-group" type="button">Tạo nhóm</button></div>
            <div class="inlineForm"><input id="group-code" maxlength="8" placeholder="Mã nhóm 8 ký tự"><button class="quickQuery" id="join-group" type="button">Tham gia</button></div>
            <p class="formMessage" id="group-message" role="status">Mỗi thành viên có thể bình chọn món trong nhóm.</p>
            <div id="group-result" class="groupResult"></div>
          </div>
      </section>
      <section id="panel-booking" class="tabPanel narrowPanel" role="tabpanel" aria-labelledby="tab-booking" tabindex="0" hidden>
        <div class="panelIntro"><span class="sectionEyebrow">Lên lịch đi ăn</span><h2>Đặt bàn</h2><p>Điền thông tin và gửi yêu cầu đặt bàn cho quán bạn chọn.</p></div>
          <form class="featurePanel" id="booking-form">
            <h3>Đặt bàn nhanh</h3>
            <label>Mã quán<input id="booking-restaurant" name="restaurantId" type="number" min="1" required placeholder="Ví dụ: 1"></label>
            <label>Họ tên<input id="booking-name" name="guestName" maxlength="100" required></label>
            <label>Email<input id="booking-email" name="email" type="email" required></label>
            <div class="twoFields"><label>Thời gian<input id="booking-date" name="bookingDate" type="datetime-local" required></label><label>Số người<input id="booking-guests" name="guestCount" type="number" min="1" max="20" value="2" required></label></div>
            <label>Ghi chú<textarea id="booking-note" name="note" maxlength="500" rows="2"></textarea></label>
            <button class="likeButton" type="submit">Gửi yêu cầu đặt bàn</button><p class="formMessage" id="booking-message" role="status"></p>
          </form>
      </section>
      <section id="panel-insights" class="tabPanel" role="tabpanel" aria-labelledby="tab-insights" tabindex="0" hidden>
        <div class="panelIntro"><span class="sectionEyebrow">Đang được yêu thích</span><h2>Xu hướng món ăn</h2><p>Tham khảo lựa chọn nổi bật từ dữ liệu NhomNhom.</p></div>
        <div class="insightsPanel"><div><h3>Món được thích nhiều</h3></div><div id="insights-list" class="insightsList"><p class="emptyState">Đang tải xu hướng…</p></div></div>
      </section>
    </div>
  </main>
  <dialog id="restaurant-dialog" class="restaurantDialog" aria-labelledby="restaurant-dialog-title">
    <div class="dialogHeader"><h2 id="restaurant-dialog-title">Chi tiết quán</h2><button class="quickQuery" id="close-restaurant-dialog" type="button" autofocus>Đóng</button></div>
    <div id="restaurant-dialog-body"></div><p id="dialog-message" class="exploreMessage" role="status"></p>
  </dialog>
  <script src="assets/tabs.js" defer></script>
  <script src="assets/restaurant-dialog.js" defer></script>
  <script src="assets/explore.js" defer></script>
  <script src="assets/app.js" defer></script>
</body>
</html>
