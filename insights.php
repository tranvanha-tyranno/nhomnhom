<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
renderPageHeader($db, 'Xu hướng', 'insights.php');
?>
<section class="pageHero compact">
  <span class="sectionEyebrow">Dữ liệu khu vực</span>
  <h1>Hôm nay mọi người<br> <em>đang thích gì?</em></h1>
  <p>Xếp hạng nhà hàng được quan tâm nhất và bảng điểm Elo món ăn nổi bật trên NhomNhom.</p>
</section>
<section class="insightsGrid">
  <div class="formCard insightsBlock">
    <div class="insightsBlockHead">
      <span class="sectionEyebrow">Điểm hẹn nổi bật</span>
      <h2>Top nhà hàng được quan tâm</h2>
    </div>
    <div id="insights-list" class="insightsList"><p class="emptyState">Đang tải xu hướng…</p></div>
  </div>
  <div class="formCard insightsBlock">
    <div class="insightsBlockHead">
      <span class="sectionEyebrow">Bảng xếp hạng Elo</span>
      <h2>Món ăn được yêu thích nhất</h2>
    </div>
    <div id="top-dishes-list" class="topDishesList"><p class="emptyState">Đang tải bảng xếp hạng…</p></div>
  </div>
</section>
<script src="assets/insights.js?v=20261119" defer></script>
<?php renderPageFooter('insights.php'); ?>
