<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';

$db = openDatabase();
$rawId = $_GET['id'] ?? null;
$id = is_string($rawId) && preg_match('/^[1-9]\d*$/', $rawId) === 1
    ? (int) filter_var($rawId, FILTER_VALIDATE_INT)
    : 0;
$restaurant = $id > 0 ? findRestaurantDetail($db, $id) : null;
$title = $restaurant ? $restaurant['name'] : 'Không tìm thấy nhà hàng';
if (!$restaurant) {
    http_response_code(404);
}
renderPageHeader($db, $title, 'index.php');
?>
<nav class="breadcrumbNav" aria-label="Đường dẫn"><a href="index.php">← Quay lại danh sách quán</a></nav>
<?php if (!$restaurant): ?>
<section class="emptyState collectionEmpty">
  <strong>Không tìm thấy nhà hàng này.</strong>
  <span>Quán có thể đã tạm ngừng hoạt động hoặc đường dẫn không đúng.</span>
  <a class="likeButton" href="index.php">Về trang chủ <?= uiIcon('arrow') ?></a>
</section>
<?php else: ?>
<section class="detailHero" data-restaurant-id="<?= (int) $restaurant['id'] ?>" data-lat="<?= htmlEscape((string) $restaurant['lat']) ?>" data-lng="<?= htmlEscape((string) $restaurant['lng']) ?>">
  <div class="detailHeroMain">
    <div class="detailBadges">
      <span class="ratingBadge">★ <?= number_format((float) $restaurant['rating'], 1) ?> (<?= (int) $restaurant['reviewCount'] ?> đánh giá)</span>
      <span class="priceBadge"><?= str_repeat('₫', max(1, min(3, (int) $restaurant['priceLevel']))) ?></span>
      <?php if ($restaurant['isDemo']): ?><span class="demoBadge">Địa điểm mẫu</span><?php endif; ?>
    </div>
    <p class="cuisineLabel"><?= htmlEscape($restaurant['cuisine'] ?: 'Ẩm thực Việt') ?></p>
    <h1><?= htmlEscape($restaurant['name']) ?></h1>
    <p class="detailDesc"><?= htmlEscape($restaurant['description']) ?></p>
    <p class="detailAddress"><?= uiIcon('pin') ?> <span><?= htmlEscape($restaurant['address']) ?></span></p>
    <?php if (!empty($restaurant['tags'])): ?>
      <div class="tagRow"><?php foreach ($restaurant['tags'] as $tag): ?><span><?= htmlEscape($tag) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
  <aside class="detailActionCard">
    <div class="detailPriceSummary">
      <span>Khoảng giá thực đơn</span>
      <strong><?= $restaurant['minPriceVnd'] > 0 ? number_format($restaurant['minPriceVnd'], 0, ',', '.') . ' đ' . ($restaurant['maxPriceVnd'] > $restaurant['minPriceVnd'] ? ' – ' . number_format($restaurant['maxPriceVnd'], 0, ',', '.') . ' đ' : '') : 'Đang cập nhật' ?></strong>
      <small><?= (int) $restaurant['menuCount'] ?> món đang phục vụ</small>
    </div>
    <label class="travelSelect detailTravel">Phương tiện chỉ đường
      <select id="detail-travel-mode">
        <option value="walking">Đi bộ</option>
        <option value="driving">Ô tô / xe máy</option>
        <option value="bicycling">Xe đạp</option>
        <option value="transit">Công cộng</option>
      </select>
    </label>
    <div class="detailActionButtons">
      <?php if ($restaurant['isDemo']): ?>
        <span class="demoNotice">Địa điểm mẫu · Chưa xác minh bản đồ</span>
      <?php else: ?>
        <a id="detail-map-link" class="mapButton" target="_blank" rel="noopener noreferrer" href="<?= htmlEscape(buildDirectionsUrl((float) $restaurant['lat'], (float) $restaurant['lng'], 'walking')) ?>">Chỉ đường Google Maps ↗</a>
      <?php endif; ?>
      <a class="likeButton" href="bookings.php?restaurant=<?= (int) $restaurant['id'] ?>"><?= uiIcon('calendar') ?> Đặt bàn tại quán</a>
      <button class="outlineButton" id="share-restaurant" type="button"><?= uiIcon('share') ?> Sao chép liên kết</button>
    </div>
  </aside>
</section>

<section class="menuSection" aria-labelledby="menu-title">
  <div class="sectionHeading">
    <div>
      <h2 id="menu-title">Thực đơn của quán</h2>
      <p>Lưu nhanh món bạn muốn thử vào bộ sưu tập yêu thích.</p>
    </div>
    <div class="menuFilterRow" role="group" aria-label="Lọc thực đơn">
      <button type="button" class="filterChip isActive" data-menu-filter="all" aria-pressed="true">Tất cả (<?= (int) $restaurant['menuCount'] ?>)</button>
      <button type="button" class="filterChip" data-menu-filter="veg" aria-pressed="false">Món chay</button>
      <button type="button" class="filterChip" data-menu-filter="mild" aria-pressed="false">Không cay</button>
    </div>
  </div>
  <?php if (empty($restaurant['menu'])): ?>
    <p class="emptyState">Quán chưa cập nhật danh sách món ăn.</p>
  <?php else: ?>
    <div class="menuGrid" id="restaurant-menu">
      <?php foreach ($restaurant['menu'] as $dish): ?>
        <article class="menuItemCard" data-veg="<?= $dish['isVegetarian'] ? '1' : '0' ?>" data-spice="<?= (int) $dish['spiceLevel'] ?>">
          <div class="menuItemTop">
            <span class="cuisineLabel"><?= htmlEscape($dish['cuisine']) ?></span>
            <strong class="menuPrice"><?= number_format((int) $dish['priceVnd'], 0, ',', '.') ?> đ</strong>
          </div>
          <h3><?= htmlEscape($dish['name']) ?></h3>
          <p><?= htmlEscape($dish['description']) ?></p>
          <div class="tagRow">
            <span><?= $dish['isVegetarian'] ? 'Món chay' : 'Món mặn' ?></span>
            <span><?= $dish['spiceLevel'] > 0 ? 'Cay cấp ' . (int) $dish['spiceLevel'] : 'Không cay' ?></span>
            <span>Elo <?= (int) $dish['eloRating'] ?></span>
          </div>
          <div class="menuItemActions">
            <button type="button" class="outlineButton saveDishBtn" data-dish-id="<?= (int) $dish['dishId'] ?>" data-dish-name="<?= htmlEscape($dish['name']) ?>">♡ Lưu món này</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<script src="assets/restaurant.js?v=20261119" defer></script>
<?php endif; ?>
<?php renderPageFooter('index.php'); ?>
