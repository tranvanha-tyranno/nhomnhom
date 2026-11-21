<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
renderPageHeader($db, 'Khám phá món', 'discover.php');
?>
<section class="pageHero">
  <span class="sectionEyebrow">Một chút cảm hứng cho bữa tới</span>
  <h1>Hợp gu thì thích.<br> <em>Thích rồi thì đi.</em></h1>
  <p>Chọn món theo ngân sách và khẩu vị. Xem dạng thẻ tập trung hoặc dạng lưới, lưu món bạn mê và mở chỉ đường ngay.</p>
</section>
<section class="discoverLayout">
  <div class="discoverMain">
    <div class="discoverToolbar">
      <label class="searchBox"><?= uiIcon('search') ?><span class="srOnly">Tìm món ăn</span><input id="discover-query" maxlength="80" placeholder="Bạn đang thèm món gì?"></label>
      <div class="discoverFilters" role="group" aria-label="Chế độ ăn">
        <button class="filterChip isActive" data-vegetarian="" aria-pressed="true">Tất cả món</button>
        <button class="filterChip" data-vegetarian="1" aria-pressed="false">Món chay</button>
      </div>
    </div>
    <div class="dishFilterRow">
      <label>Ngân sách / món<select id="discover-budget"><option value="0">Không giới hạn</option><option value="30000">Đến 30.000 đ</option><option value="50000">Đến 50.000 đ</option><option value="100000">Đến 100.000 đ</option><option value="200000">Đến 200.000 đ</option></select></label>
      <label>Độ cay tối đa<select id="discover-spice"><option value="3">Ăn cay thoải mái</option><option value="0">Không cay</option><option value="1">Cay nhẹ</option><option value="2">Cay vừa</option></select></label>
      <label>Ưu tiên<select id="discover-sort"><option value="rating">Đánh giá cao</option><option value="price">Giá thấp trước</option></select></label>
      <button class="resetFilters" type="button" id="reset-filters">Đặt lại</button>
    </div>
    <div class="surpriseBar">
      <div><b>Vòng quay “Hôm nay ăn gì?”</b><span>Để NhomNhom quay ngẫu nhiên một món đúng ngân sách và khẩu vị của bạn.</span></div>
      <button class="likeButton" id="surprise-dish" type="button"><?= uiIcon('spark') ?> Chọn giúp tôi</button>
    </div>
    <div class="discoveryStatus">
      <span id="discovery-total" role="status">Đang tìm món…</span>
      <div class="viewModeGroup" role="group" aria-label="Chế độ hiển thị">
        <button type="button" class="filterChip isActive" data-view-mode="card" aria-pressed="true"><?= uiIcon('cards') ?> Thẻ</button>
        <button type="button" class="filterChip" data-view-mode="grid" aria-pressed="false"><?= uiIcon('grid') ?> Lưới</button>
      </div>
    </div>
    <div id="discovery-card" class="discoveryCard" aria-busy="true"><p class="emptyState">Đang tải món gợi ý…</p></div>
    <div class="shortcutHint" id="shortcut-hint">Phím tắt: <kbd>←</kbd> Bỏ qua · <kbd>→</kbd> Thích món · <kbd>Space</kbd> Chọn giúp tôi</div>
    <p id="discovery-message" class="formMessage" role="status"></p>
  </div>
  <aside class="sidePanel">
    <div class="sidePanelHead"><span class="sectionEyebrow">Gu riêng của bạn</span><span id="taste-count">0 món đã lưu</span></div>
    <h3>Để món ngon<br>dẫn lối cuộc hẹn.</h3>
    <p>Bấm thích để thêm món vào bộ sưu tập. Xem thực đơn chi tiết của quán hoặc đặt bàn ngay chỉ với một chạm.</p>
    <div class="discoverySteps"><span><i>01</i> Lọc theo khẩu vị</span><span><i>02</i> Lưu món yêu thích</span><span><i>03</i> Hẹn nhau tại quán</span></div>
    <a class="outlineButton" href="favorites.php"><?= uiIcon('heart') ?> Món đã lưu</a>
    <a class="sideTextLink" href="profile.php">Thiết lập gu mặc định →</a>
  </aside>
</section>
<script src="assets/discover.js?v=20261119" defer></script>
<?php renderPageFooter('discover.php'); ?>
