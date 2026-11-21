<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
}
try {
    foreach (array_keys($_GET) as $key) {
        if (!in_array($key, ['q', 'limit', 'offset'], true)) throw new InvalidArgumentException('Tham số danh mục nhóm không hợp lệ.');
    }
    $result = listUniqueGroupDishes(openDatabase(), validateDiscoveryOptions($_GET));
    sendJson(['success' => true, 'data' => $result['data'], 'meta' => $result['meta'], 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Group catalog failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể tải danh sách món. Vui lòng thử lại.'], 500);
}
