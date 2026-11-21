<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
$account = requireNomNomAccount($db);
renderPageHeader($db, 'Hồ sơ khẩu vị', 'profile.php');
?>
<section class="pageHero compact">
  <span class="sectionEyebrow">Hồ sơ cá nhân</span>
  <h1>Ăn đúng gu,<br> <em>đi đúng hội.</em></h1>
  <p>Thiết lập khẩu vị và phương tiện di chuyển mặc định để mọi gợi ý phù hợp ngay từ lần đầu.</p>
</section>
<section class="profileLayout">
  <form class="formCard" id="profile-form">
    <h2>Khẩu vị &amp; di chuyển</h2>
    <label>Tên hiển thị<input id="profile-name" maxlength="80" value="<?= htmlEscape($account['displayName']) ?>" required></label>
    <label>Ngân sách tối đa (VNĐ)<input id="profile-budget" type="number" min="0" step="1000" placeholder="0 = không giới hạn"></label>
    <label class="checkLine"><input id="profile-vegetarian" type="checkbox"> Chỉ gợi ý món chay</label>
    <label>Phương tiện chỉ đường
      <select id="profile-mode">
        <option value="walking">Đi bộ</option>
        <option value="driving">Ô tô / xe máy</option>
        <option value="bicycling">Xe đạp</option>
        <option value="transit">Công cộng</option>
      </select>
    </label>
    <button class="likeButton" type="submit">Lưu thay đổi</button>
    <p id="profile-message" class="formMessage" role="status"></p>
  </form>
  <div class="formCard profileWelcome">
    <span class="sectionEyebrow">Tài khoản</span>
    <h2><?= htmlEscape($account['displayName']) ?></h2>
    <p><?= htmlEscape($account['email']) ?></p>
    <div class="profileStatsGrid" aria-label="Thống kê cá nhân">
      <a class="profileStatCard" href="favorites.php"><strong id="stat-favorites">–</strong><span>Món yêu thích</span></a>
      <a class="profileStatCard" href="bookings.php"><strong id="stat-bookings">–</strong><span>Lịch hẹn sắp tới</span></a>
    </div>
    <div class="profilePerks">
      <span><?= uiIcon('spark') ?> Gợi ý tự động theo khẩu vị</span>
      <span><?= uiIcon('heart') ?> Lưu món yêu thích theo tài khoản</span>
      <span><?= uiIcon('group') ?> Bình chọn món cùng hội bạn</span>
    </div>
  </div>
</section>
<script src="assets/profile.js?v=20261119" defer></script>
<?php renderPageFooter('profile.php'); ?>
