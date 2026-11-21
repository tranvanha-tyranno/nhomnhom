<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
renderPageHeader($db, 'Trang chủ', 'index.php');
?>
<section class="foodHero" aria-labelledby="hero-title">
 <div class="foodHeroCopy">
  <span class="foodPill"><?= uiIcon('spark') ?> Bữa ngon bắt đầu ở đây</span>
  <h1 id="hero-title">Hôm nay<br>mình ăn gì?</h1>
  <p>Đi theo chiếc bụng đói. Tìm món hợp gu,<br>xem thực đơn quán, lưu món muốn thử và mở chỉ đường ngay.</p>
  <div class="heroButtons">
   <a class="likeButton" href="discover.php">Khám phá món ngon <?= uiIcon('arrow') ?></a>
   <a class="heroSecondary" href="discover.php?surprise=1"><?= uiIcon('spark') ?> Chọn giúp tôi</a>
  </div>
  <div class="heroLittleNote"><?= uiIcon('heart') ?> Một mình hay cả hội, đều có món hợp gu.</div>
 </div>
 <div class="heroFoodImage" role="img" aria-label="Mâm món ăn Việt đầy màu sắc"><span class="heroSticker">Ăn ngon<br><b>đúng gu!</b></span><span class="imageCredit">Ảnh minh họa ẩm thực</span></div>
</section>

<section class="moodSection" aria-labelledby="mood-title">
 <div class="sectionHeading">
  <div><h2 id="mood-title">Chiếc bụng đang muốn gì?</h2><p>Chạm một nhóm món, tìm ngay quán phù hợp.</p></div>
  <a class="textLink" href="discover.php">Xem tất cả món <?= uiIcon('arrow') ?></a>
 </div>
 <div class="moodGrid">
 <?php foreach ([['noodle','Phở nóng','phở'],['soup','Bún đậm đà','bún'],['rice','Cơm nhà','cơm'],['leaf','Ăn chay','chay'],['bread','Bánh mì','bánh mì'],['spark','Thử món mới','']] as [$icon,$label,$query]): ?>
  <button class="moodButton" type="button" data-query="<?= htmlEscape($query) ?>" aria-pressed="false"><span class="moodIconWrap" aria-hidden="true"><?= uiIcon($icon) ?></span><b><?= $label ?></b></button>
 <?php endforeach; ?>
 </div>
</section>

<section class="restaurantSection" id="restaurant-search" aria-labelledby="restaurant-title">
 <div class="sectionHeading">
  <div><span class="sectionEyebrow">Tìm món, xem thực đơn, chỉ đường ngay</span><h2 id="restaurant-title">Điểm hẹn cho bữa ngon tiếp theo</h2></div>
  <span id="result-count" class="resultCount" role="status">Đang tải nhà hàng…</span>
 </div>
 <form class="restaurantSearch" id="search-form" role="search">
  <label class="searchBox" for="query"><?= uiIcon('search') ?><span class="srOnly">Tìm món hoặc nhà hàng</span><input id="query" name="q" maxlength="80" placeholder="Tìm món ăn, tên quán hoặc khu vực…"></label>
  <button class="likeButton" type="submit">Tìm quán <?= uiIcon('arrow') ?></button>
 </form>
 <div class="smartFilterBar" aria-label="Bộ lọc nhà hàng">
  <div class="filterSelectGroup">
   <label class="travelSelect">Mức giá
    <select id="price-filter">
     <option value="0">Tất cả mức giá</option>
     <option value="1">Bình dân (₫)</option>
     <option value="2">Tầm trung (₫₫)</option>
     <option value="3">Cao cấp (₫₫₫)</option>
    </select>
   </label>
   <label class="travelSelect">Sắp xếp
    <select id="sort-filter">
     <option value="relevance">Phù hợp nhất</option>
     <option value="rating">Đánh giá cao nhất</option>
     <option value="popular">Nhiều đánh giá</option>
     <option value="price_asc">Giá mềm trước</option>
    </select>
   </label>
   <label class="travelSelect">Di chuyển
    <select id="travel-mode">
     <option value="walking">Đi bộ</option>
     <option value="driving">Ô tô / xe máy</option>
     <option value="bicycling">Xe đạp</option>
     <option value="transit">Công cộng</option>
    </select>
   </label>
  </div>
  <div class="filterToggleGroup">
   <button class="filterChip" id="verified-filter" type="button" aria-pressed="false"><?= uiIcon('check') ?> Chỉ quán xác minh</button>
   <button class="filterChip" id="near-me-btn" type="button" aria-pressed="false"><?= uiIcon('compass') ?> Tính khoảng cách từ tôi</button>
  </div>
 </div>
 <div class="searchOptions">
  <div class="quickQueryRow"><span>Gợi ý:</span><button class="quickQuery" data-query="Quận 7" type="button">Quận 7</button><button class="quickQuery" data-query="bún bò" type="button">Bún bò</button><button class="quickQuery" data-query="phở" type="button">Phở</button><button class="quickQuery" data-query="chay" type="button">Món chay</button><button class="quickQuery" data-query="cơm tấm" type="button">Cơm tấm</button></div>
 </div>
 <div id="results" aria-live="polite" aria-busy="true"><p class="emptyState">Đang tìm nhà hàng…</p></div>
</section>

<section class="togetherBanner">
 <div class="togetherArt" aria-hidden="true">
  <span class="togetherIcon"><?= uiIcon('noodle') ?></span>
  <span class="togetherPlus">+</span>
  <span class="togetherIcon"><?= uiIcon('group') ?></span>
  <span class="togetherPlus">=</span>
  <span class="togetherIcon accent"><?= uiIcon('heart') ?></span>
 </div>
 <div>
  <h2>Mỗi người một gu.<br>Cả hội vẫn ăn cùng nhau.</h2>
  <p>Tạo nhóm, cùng chọn món và tìm điểm chung cho bữa tới.</p>
  <a class="likeButton" href="groups.php">Rủ hội bạn <?= uiIcon('group') ?></a>
 </div>
</section>

<section class="journeyLinks" aria-label="Không gian của bạn">
 <a href="favorites.php"><?= uiIcon('heart') ?><div><h3>Để dành một món ngon</h3><p>Mở lại những món bạn đã thích.</p></div><?= uiIcon('arrow') ?></a>
 <a href="bookings.php"><?= uiIcon('calendar') ?><div><h3>Đừng quên cuộc hẹn</h3><p>Lên lịch và quản lý yêu cầu đặt bàn.</p></div><?= uiIcon('arrow') ?></a>
 <a href="profile.php"><?= uiIcon('user') ?><div><h3>Gu của riêng bạn</h3><p>Đặt ngân sách và khẩu vị mặc định.</p></div><?= uiIcon('arrow') ?></a>
</section>
<script src="assets/search.js?v=20261121" defer></script>
<?php renderPageFooter('index.php'); ?>
