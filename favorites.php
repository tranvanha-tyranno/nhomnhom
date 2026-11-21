<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
$account = currentNomNomAccount($db);
renderPageHeader($db, 'Món yêu thích', 'favorites.php');
?>
<section class="pageHero"><span class="sectionEyebrow">BỘ SƯU TẬP CỦA RIÊNG BẠN</span><h1>Đã thích thì<br> <em>để dành một chỗ.</em></h1><p>Tất cả những món khiến bạn muốn thử. Chọn một món và lên lịch cho bữa ngon tiếp theo.</p></section>
<?php if (!$account): ?><div class="infoBanner"><?= uiIcon('user') ?><span>Bạn đang lưu trên phiên này. <a href="login.php?return=favorites.php">Đăng nhập</a> trước khi chọn món để lưu theo tài khoản.</span></div><?php endif; ?>
<section aria-label="Bộ sưu tập món yêu thích"><div class="collectionToolbar"><label class="searchBox"><?= uiIcon('search') ?><span class="srOnly">Tìm trong món yêu thích</span><input id="favorite-query" placeholder="Tìm trong bộ sưu tập…" maxlength="80"></label><label class="travelSelect">Sắp xếp<select id="favorite-sort"><option value="recent">Mới lưu gần đây</option><option value="price">Giá thấp đến cao</option><option value="name">Tên món A → Z</option></select></label><span id="favorite-count" class="resultCount" role="status">Đang tải…</span></div>
    <p class="collectionHint">Mỗi món hiển thị quán đang có giá thấp nhất trong danh mục. Giá và địa điểm mẫu được ghi rõ.</p>
    <p class="formMessage" id="favorite-message" role="status"></p><div id="favorite-list" class="resultsGrid" aria-live="polite" aria-busy="true"><p class="emptyState">Đang mở bộ sưu tập…</p></div>
</section>
<script src="assets/favorites.js" defer></script>
<?php renderPageFooter('favorites.php'); ?>
