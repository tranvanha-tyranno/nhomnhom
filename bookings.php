<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php'; require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
$account = requireNomNomAccount($db);
$restaurantId = filter_var($_GET['restaurant'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$restaurant = null;
if ($restaurantId) {
    $statement = $db->prepare("SELECT id, name, address, is_demo FROM restaurants WHERE id=? AND status='active'");
    $statement->execute([$restaurantId]); $restaurant = $statement->fetch() ?: null;
}
renderPageHeader($db, 'Đặt bàn', 'bookings.php');
?>
<section class="pageHero compact"><span class="sectionEyebrow">CÓ HẸN VỚI MỘT BỮA NGON</span><h1>Chọn một chỗ.<br> <em>Hẹn một bữa vui.</em></h1><p>Tìm quán, chọn giờ và gửi yêu cầu. Mọi cuộc hẹn sắp tới nằm ngay tại đây.</p></section>
<div class="infoBanner"><?= uiIcon('calendar') ?><span>Yêu cầu đặt bàn trên NhomNhom cần được quán xác nhận. Với địa điểm mẫu, yêu cầu chỉ dùng để trải nghiệm.</span></div>
<section class="splitPage"><form class="formCard" id="booking-form"><span class="sectionEyebrow">01 / LÊN LỊCH HẸN</span><h2>Đặt bàn mới</h2>
    <div class="restaurantPicker"><label for="restaurant-picker">Chọn nhà hàng</label><input id="restaurant-picker" type="search" maxlength="80" autocomplete="off" placeholder="Tìm nhà hàng theo tên hoặc món…" aria-describedby="restaurant-picker-help"><small id="restaurant-picker-help">Tìm rồi chọn một quán trong danh sách bên dưới.</small><div id="restaurant-options" class="restaurantOptions" aria-live="polite"></div></div>
    <input id="booking-restaurant" type="hidden" value="<?= $restaurant ? (int) $restaurant['id'] : '' ?>">
    <div class="selectedRestaurant" id="selected-restaurant" <?= $restaurant ? '' : 'hidden' ?>><?php if ($restaurant): ?><strong><?= htmlEscape($restaurant['name']) ?></strong><span><?= htmlEscape($restaurant['address']) ?></span><small><?= $restaurant['is_demo'] ? 'Địa điểm mẫu · Yêu cầu trải nghiệm' : 'Yêu cầu cần được nhà hàng xác nhận' ?></small><?php endif; ?></div>
    <label>Họ tên<input id="booking-name" maxlength="100" autocomplete="name" value="<?= htmlEscape($account['displayName']) ?>" required></label><label>Email<input id="booking-email" type="email" autocomplete="email" value="<?= htmlEscape($account['email']) ?>" required></label>
    <div class="twoFields"><label>Ngày & giờ hẹn<input id="booking-date" type="datetime-local" required><small>Giờ Việt Nam (UTC+7)</small></label><label>Số người<input id="booking-guests" type="number" min="1" max="20" value="2" required></label></div>
    <label>Lời nhắn cho quán <span class="optionalLabel">(không bắt buộc)</span><textarea id="booking-note" maxlength="500" rows="3" placeholder="Ví dụ: bàn gần cửa sổ, có trẻ nhỏ…"></textarea></label><button class="likeButton" type="submit" id="booking-submit" disabled>Gửi yêu cầu đặt bàn <?= uiIcon('arrow') ?></button><p id="booking-message" class="formMessage" role="status"></p>
</form><div class="formCard"><span class="sectionEyebrow">02 / NHỮNG CUỘC HẸN</span><h2>Lịch đặt bàn của bạn</h2><div class="bookingTabs" role="group" aria-label="Lọc lịch đặt bàn"><button type="button" class="filterChip isActive" data-booking-filter="upcoming" aria-pressed="true">Sắp tới</button><button type="button" class="filterChip" data-booking-filter="all" aria-pressed="false">Tất cả</button><button type="button" class="filterChip" data-booking-filter="cancelled" aria-pressed="false">Đã hủy</button></div><div id="booking-list" aria-live="polite"><p class="emptyState">Đang tải lịch hẹn…</p></div></div></section>
<script src="assets/bookings.js" defer></script>
<?php renderPageFooter('bookings.php'); ?>
