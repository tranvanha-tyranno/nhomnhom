<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    sendJson(['success' => false, 'data' => null, 'error' => 'Chỉ hỗ trợ phương thức GET.'], 405);
}

$rawId = $_GET['id'] ?? null;
$id = is_string($rawId) && preg_match('/^[1-9]\d*$/', $rawId) === 1
    ? filter_var($rawId, FILTER_VALIDATE_INT)
    : false;
if ($id === false) {
    sendJson(['success' => false, 'data' => null, 'error' => 'Mã nhà hàng không hợp lệ.'], 400);
}

try {
    $detail = findRestaurantDetail(openDatabase(), (int) $id);
    if ($detail === null) {
        sendJson(['success' => false, 'data' => null, 'error' => 'Không tìm thấy nhà hàng.'], 404);
    }
    sendJson(['success' => true, 'data' => $detail, 'error' => null]);
} catch (Throwable $error) {
    error_log('Restaurant detail failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể tải thông tin nhà hàng.'], 500);
}
