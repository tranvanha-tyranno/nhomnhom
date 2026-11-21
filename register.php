<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$db = openDatabase(); $error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        requireNomNomCsrf($_POST);
        $account = createNomNomAccount($db, validateAuthPayload(array_merge($_POST, ['action' => 'register'])));
        header('Location: ' . (isNomNomMerchant($account) ? 'merchant.php' : 'discover.php'));
        exit;
    }
    catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
    catch (Throwable $exception) { error_log('Registration failed: ' . $exception->getMessage()); $error = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại.'; }
}
$selectedRole = (($_POST['role'] ?? $_GET['role'] ?? 'customer') === 'merchant') ? 'merchant' : 'customer';
$csrfToken = nomnomCsrfToken();
renderAuthHeader('Tạo tài khoản', true);
?>
<div class="authHeading"><span class="brandMark"><?= uiIcon('brand') ?></span><h2>Mỗi khẩu vị, một hành trình.</h2><p>Tạo tài khoản khách ăn uống hoặc đăng ký làm Chủ cửa hàng.</p></div>
<form class="authCard" method="post">
    <?php if ($error): ?><p class="formError" role="alert"><?= htmlEscape($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrfToken" value="<?= htmlEscape($csrfToken) ?>">
    <fieldset class="roleSelector">
        <legend>Loại tài khoản</legend>
        <div class="roleOptions">
            <label class="roleOption">
                <input type="radio" name="role" value="customer" <?= $selectedRole === 'customer' ? 'checked' : '' ?>>
                <span class="roleCard"><?= uiIcon('user') ?><strong>Khách ăn uống</strong><small>Tìm món, lưu yêu thích & đặt bàn</small></span>
            </label>
            <label class="roleOption">
                <input type="radio" name="role" value="merchant" <?= $selectedRole === 'merchant' ? 'checked' : '' ?>>
                <span class="roleCard"><?= uiIcon('store') ?><strong>Chủ cửa hàng</strong><small>Tạo quán ăn, đăng thực đơn & duyệt bàn</small></span>
            </label>
        </div>
    </fieldset>
    <label for="display-name">Tên của bạn hoặc tên cửa hàng</label>
    <input id="display-name" name="displayName" maxlength="80" autocomplete="name" placeholder="Mọi người gọi bạn là gì?" required>
    <label for="email">Địa chỉ email</label>
    <input id="email" name="email" type="email" autocomplete="email" placeholder="ban@example.com" required>
    <label for="password">Mật khẩu</label>
    <div class="passwordField"><input id="password" name="password" type="password" minlength="8" maxlength="200" autocomplete="new-password" placeholder="Tạo mật khẩu của bạn" aria-describedby="password-help" required><button type="button" data-password-toggle aria-controls="password" aria-pressed="false">Hiện</button></div>
    <small id="password-help">Sử dụng ít nhất 8 ký tự để bảo vệ tài khoản.</small>
    <button class="likeButton authSubmit" type="submit">Bắt đầu hành trình <?= uiIcon('arrow') ?></button>
    <p class="authHint">Đã là một phần của NhomNhom? <a href="login.php">Đăng nhập</a></p>
</form>
<div class="authBenefits"><span><?= uiIcon('heart') ?> Lưu món yêu thích</span><span><?= uiIcon('group') ?> Chọn cùng bạn bè</span></div>
<p class="authFineprint">Khẩu vị riêng. Trải nghiệm riêng.<br>Một không gian cho mọi cuộc hẹn ăn ngon.</p>
<?php renderAuthFooter(); ?>
