<?php
declare(strict_types=1);
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Tìm món ăn yêu thích và mở chỉ đường Google Maps.">
  <title>NhomNhom — Tìm món ăn</title>
  <link rel="stylesheet" href="assets/app.css">
</head>
<body>
  <main class="appShell">
    <section class="heroBand" aria-labelledby="app-title">
      <div class="heroContent">
        <div class="brandBlock">
          <div class="brandBadge" aria-label="NhomNhom"><span aria-hidden="true">🍜</span><span>NhomNhom</span></div>
          <h1 id="app-title">NhomNhom</h1>
          <p>Tìm món hợp gu rồi mở Google Maps đến điểm ăn gần đúng với lựa chọn của bạn.</p>
        </div>
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
      </div>
    </section>
    <section class="discoveryBand" aria-labelledby="discovery-title">
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
    <section class="featureBand" aria-labelledby="feature-title">
      <div class="featureContent">
        <div class="featureIntro"><span class="sectionEyebrow">Hồ sơ & kết nối</span><h2 id="feature-title">Ăn đúng gu, đi cùng bạn</h2><p>Thiết lập khẩu vị, tạo nhóm chọn món, đặt bàn và xem xu hướng từ dữ liệu NomNom.</p></div>
        <div class="featureGrid">
          <form class="featurePanel" id="profile-form">
            <h3>Hồ sơ khẩu vị</h3>
            <label>Tên hiển thị<input id="profile-name" name="displayName" maxlength="80" required></label>
            <label>Ngân sách tối đa<input id="profile-budget" name="maxPriceVnd" type="number" min="0" step="1000" placeholder="0 = không giới hạn"></label>
            <label class="checkLine"><input id="profile-vegetarian" name="vegetarianOnly" type="checkbox"> Chỉ gợi ý món chay</label>
            <label>Phương tiện<select id="profile-mode" name="travelMode"><option value="walking">Đi bộ</option><option value="driving">Xe</option><option value="bicycling">Xe đạp</option><option value="transit">Transit</option></select></label>
            <button class="likeButton" type="submit">Lưu khẩu vị</button><p class="formMessage" id="profile-message" role="status"></p>
          </form>
          <div class="featurePanel">
            <h3>Chọn món theo nhóm</h3>
            <div class="inlineForm"><input id="group-name" maxlength="80" placeholder="Tên của bạn"><button class="quickQuery" id="create-group" type="button">Tạo nhóm</button></div>
            <div class="inlineForm"><input id="group-code" maxlength="8" placeholder="Mã nhóm 8 ký tự"><button class="quickQuery" id="join-group" type="button">Tham gia</button></div>
            <p class="formMessage" id="group-message" role="status">Mỗi thành viên có thể bình chọn món trong nhóm.</p>
            <div id="group-result" class="groupResult"></div>
          </div>
          <form class="featurePanel" id="booking-form">
            <h3>Đặt bàn nhanh</h3>
            <label>Mã quán<input id="booking-restaurant" name="restaurantId" type="number" min="1" required placeholder="Ví dụ: 1"></label>
            <label>Họ tên<input id="booking-name" name="guestName" maxlength="100" required></label>
            <label>Email<input id="booking-email" name="email" type="email" required></label>
            <div class="twoFields"><label>Thời gian<input id="booking-date" name="bookingDate" type="datetime-local" required></label><label>Số người<input id="booking-guests" name="guestCount" type="number" min="1" max="20" value="2" required></label></div>
            <label>Ghi chú<textarea id="booking-note" name="note" maxlength="500" rows="2"></textarea></label>
            <button class="likeButton" type="submit">Gửi yêu cầu đặt bàn</button><p class="formMessage" id="booking-message" role="status"></p>
          </form>
        </div>
        <div class="insightsPanel"><div><span class="sectionEyebrow">Dữ liệu khu vực</span><h3>Xu hướng món được thích</h3></div><div id="insights-list" class="insightsList"><p class="emptyState">Đang tải xu hướng…</p></div></div>
      </div>
    </section>
    <section class="resultsBand" aria-labelledby="results-title">
      <div class="resultsContent">
        <div class="resultsHeader">
          <div><span class="sectionEyebrow">Danh sách gợi ý</span><h2 id="results-title">Món phù hợp</h2></div>
          <div class="resultCount" id="result-count" aria-live="polite">📍 <span>Đang tải…</span></div>
        </div>
        <p class="demoNotice">Bản demo sử dụng quán ăn, địa chỉ và đánh giá giả lập. Các địa điểm này không dùng để chỉ đường thực tế.</p>
        <div id="results" aria-live="polite"><p class="emptyState">Đang tìm quán ăn…</p></div>
      </div>
    </section>
  </main>
  <script src="assets/app.js" defer></script>
</body>
</html>
