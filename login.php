<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase();
$error = '';
$returnTo = safeNomNomReturn($_GET['return'] ?? $_POST['return'] ?? 'discover.php');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $payload = readJsonObject();
    } catch (InvalidArgumentException) {
        $payload = $_POST;
    }
    try {
        requireNomNomCsrf($payload);
        loginNomNomAccount($db, validateAuthPayload(array_merge($payload, ['action' => 'login'])));
        header('Location: ' . $returnTo); exit;
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
    catch (Throwable $exception) { error_log('Login failed: ' . $exception->getMessage()); $error = 'Không thể đăng nhập lúc này. Vui lòng thử lại.'; }
}
$csrfToken = nomnomCsrfToken();
renderAuthHeader('Đăng nhập');
?>
<div class="authHeading"><span class="brandMark"><?= uiIcon('brand') ?></span><h2>Chào bạn, lại là NhomNhom.</h2><p>Đăng nhập để tiếp tục hành trình khám phá món ngon.</p></div>
<form class="authCard" method="post">
    <?php if ($error): ?><p class="formError" role="alert"><?= htmlEscape($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrfToken" value="<?= htmlEscape($csrfToken) ?>">
    <input type="hidden" name="return" value="<?= htmlEscape($returnTo) ?>">
    <label for="email">Địa chỉ email</label>
    <input id="email" name="email" type="email" autocomplete="email" placeholder="ban@example.com" required>
    <label for="password">Mật khẩu</label>
    <div class="passwordField"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu của bạn" required><button type="button" data-password-toggle aria-controls="password" aria-pressed="false">Hiện</button></div>
    <button class="likeButton authSubmit" type="submit">Đăng nhập <?= uiIcon('arrow') ?></button>
    <p class="authHint">Bạn mới ghé NhomNhom? <a href="register.php">Tạo tài khoản</a></p>
</form>
<div class="authExplore"><span class="sectionEyebrow">CHỈ MUỐN KHÁM PHÁ?</span><p>Một món mới có thể trở thành món bạn thích nhất.</p><a class="outlineButton" href="discover.php"><?= uiIcon('discover') ?> Khám phá món ngay <span>↗</span></a></div>
<p class="authFineprint">Ăn đúng gu. Chọn đúng hội.<br>Mọi cuộc hẹn ngon lành đều bắt đầu từ một lựa chọn.</p>
<?php renderAuthFooter(); ?>
