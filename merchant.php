<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';

$db = openDatabase();
$account = currentNomNomAccount($db);
if (!$account) {
    header('Location: login.php?return=' . rawurlencode('merchant.php'));
    exit;
}
$isMerchant = isNomNomMerchant($account);
if (!$isMerchant) {
    http_response_code(403);
}
renderPageHeader($db, 'Kênh Cửa hàng', 'merchant.php');
?>
<?php if (!$isMerchant): ?>
<section class="emptyState collectionEmpty">
  <strong>Tài khoản hiện tại là Khách ăn uống.</strong>
  <span>Để tạo quán và đăng món ăn, hãy đăng ký tài khoản với vai trò <b>Chủ cửa hàng</b> hoặc đăng nhập bằng tài khoản cửa hàng.</span>
  <div class="cardActionRow" style="justify-content:center;margin-top:12px;">
    <a class="likeButton" href="register.php?role=merchant"><?= uiIcon('store') ?> Đăng ký Chủ cửa hàng</a>
    <a class="outlineButton" href="index.php">Về trang chủ</a>
  </div>
</section>
<?php else: ?>
<section class="pageHero merchantHero">
  <div>
    <span class="sectionEyebrow">Kênh đối tác cửa hàng</span>
    <h1>Quản lý quán ăn<br>& thực đơn của bạn.</h1>
    <p>Tạo quán mới kèm tọa độ Google Maps, đăng món lên thực đơn và xác nhận lịch đặt bàn từ khách hàng.</p>
  </div>
  <div class="merchantStats" id="merchant-stats">
    <div class="merchantStatBox"><strong id="stat-stores">0</strong><span>Quán đang mở</span></div>
    <div class="merchantStatBox"><strong id="stat-dishes">0</strong><span>Món thực đơn</span></div>
    <div class="merchantStatBox"><strong id="stat-bookings">0</strong><span>Lịch đặt bàn</span></div>
  </div>
</section>

<section class="merchantFormsGrid">
  <form class="formCard merchantForm" id="create-store-form">
    <div class="merchantFormHead">
      <span class="sectionEyebrow">Bước 1 · Địa điểm thực tế</span>
      <h2><?= uiIcon('store') ?> Tạo quán ăn mới</h2>
    </div>
    <label for="store-name">Tên quán ăn</label>
    <input id="store-name" name="name" maxlength="160" placeholder="VD: Bún Đậu Mẹt Tre — Quận 7" required>

    <div class="twoFields">
      <div>
        <label for="store-city">Thành phố</label>
        <select id="store-city" name="cityId" required></select>
      </div>
      <div>
        <label for="store-area">Quận / Khu vực</label>
        <input id="store-area" name="areaName" maxlength="80" placeholder="VD: Quận 7" required>
      </div>
    </div>

    <label for="store-address">Địa chỉ đầy đủ</label>
    <input id="store-address" name="address" maxlength="240" placeholder="VD: 19 Nguyễn Hữu Thọ, P. Tân Phong, Quận 7, TP.HCM" required>

    <div class="geoHelperBox">
      <div class="geoHelperTop">
        <label for="store-map-paste">Tọa độ Google Maps (để khách bấm Chỉ đường)</label>
        <button type="button" class="filterChip" id="btn-fill-gps"><?= uiIcon('compass') ?> Lấy vị trí hiện tại</button>
      </div>
      <input id="store-map-paste" type="text" placeholder="Dán link Google Maps (có @10.732,106.700) hoặc nhập tay bên dưới">
      <div class="twoFields">
        <div>
          <label for="store-lat">Vĩ độ (Latitude)</label>
          <input id="store-lat" name="latitude" type="number" step="any" min="-90" max="90" placeholder="10.7326" required>
        </div>
        <div>
          <label for="store-lng">Kinh độ (Longitude)</label>
          <input id="store-lng" name="longitude" type="number" step="any" min="-180" max="180" placeholder="106.6998" required>
        </div>
      </div>
    </div>

    <div class="twoFields">
      <div>
        <label for="store-cuisine">Nhóm ẩm thực</label>
        <select id="store-cuisine" name="cuisineId" required></select>
      </div>
      <div>
        <label for="store-price">Mức giá</label>
        <select id="store-price" name="priceLevel">
          <option value="1">Bình dân (₫)</option>
          <option value="2" selected>Tầm trung (₫₫)</option>
          <option value="3">Cao cấp (₫₫₫)</option>
        </select>
      </div>
    </div>

    <label for="store-desc">Mô tả quán</label>
    <textarea id="store-desc" name="description" rows="3" maxlength="500" placeholder="Giới thiệu ngắn về hương vị, không gian và điểm đặc trưng của quán…" required></textarea>

    <button class="likeButton" type="submit" id="btn-create-store">Đăng quán lên NhomNhom <?= uiIcon('arrow') ?></button>
    <p class="formMessage" id="store-form-msg" role="status"></p>
  </form>

  <form class="formCard merchantForm" id="add-dish-form">
    <div class="merchantFormHead">
      <span class="sectionEyebrow">Bước 2 · Thực đơn quán</span>
      <h2><?= uiIcon('noodle') ?> Thêm món ăn mới</h2>
    </div>
    <label for="dish-restaurant">Chọn quán của bạn</label>
    <select id="dish-restaurant" name="restaurantId" required>
      <option value="">— Tạo quán trước để thêm món —</option>
    </select>

    <label for="dish-name">Tên món ăn</label>
    <input id="dish-name" name="dishName" maxlength="120" placeholder="VD: Bún đậu tá lả đặc biệt" required>

    <div class="twoFields">
      <div>
        <label for="dish-category">Loại món</label>
        <select id="dish-category" name="categoryId" required></select>
      </div>
      <div>
        <label for="dish-cuisine">Ẩm thực</label>
        <select id="dish-cuisine" name="cuisineId" required></select>
      </div>
    </div>

    <div class="twoFields">
      <div>
        <label for="dish-price">Giá bán (VNĐ)</label>
        <input id="dish-price" name="priceVnd" type="number" min="1000" max="100000000" step="1000" value="45000" required>
      </div>
      <div>
        <label for="dish-spice">Độ cay</label>
        <select id="dish-spice" name="spiceLevel">
          <option value="0">Không cay (0)</option>
          <option value="1">Cay nhẹ (1)</option>
          <option value="2">Cay vừa (2)</option>
          <option value="3">Cay nhiều (3)</option>
        </select>
      </div>
    </div>

    <label class="checkboxInline" for="dish-veg">
      <input id="dish-veg" name="isVegetarian" type="checkbox" value="1">
      <span>Đây là món chay</span>
    </label>

    <label for="dish-desc">Mô tả món ăn</label>
    <textarea id="dish-desc" name="description" rows="3" maxlength="400" placeholder="Thành phần chính, khẩu phần hoặc cách ăn kèm ngon nhất…" required></textarea>

    <button class="likeButton" type="submit" id="btn-add-dish">Thêm món vào thực đơn <?= uiIcon('arrow') ?></button>
    <p class="formMessage" id="dish-form-msg" role="status"></p>
  </form>
</section>

<section class="merchantSection" aria-labelledby="my-stores-title">
  <div class="sectionHeading">
    <div><span class="sectionEyebrow">Đang hiển thị trên trang chủ & bản đồ</span><h2 id="my-stores-title">Quán ăn & Thực đơn của bạn</h2></div>
  </div>
  <div id="merchant-stores-list" class="merchantStoresList"><p class="emptyState">Đang tải danh sách quán…</p></div>
</section>

<section class="merchantSection" aria-labelledby="my-bookings-title">
  <div class="sectionHeading">
    <div><span class="sectionEyebrow">Khách đặt bàn qua NhomNhom</span><h2 id="my-bookings-title">Lịch đặt bàn tại quán</h2></div>
  </div>
  <div id="merchant-bookings-list" class="merchantBookingsList"><p class="emptyState">Đang tải lịch đặt bàn…</p></div>
</section>
<script src="assets/merchant.js?v=20261121" defer></script>
<?php endif; ?>
<?php renderPageFooter('merchant.php'); ?>
