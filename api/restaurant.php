<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
    }
    if (array_diff(array_keys($_GET), ['id'])) throw new InvalidArgumentException('Tham số không hợp lệ.');
    $id = parsePositiveInteger($_GET['id'] ?? '', 'Mã quán');
    if ($id < 1) throw new InvalidArgumentException('Mã quán không hợp lệ.');
    $place = findRestaurantDetail(openDatabase(), $id);
    if ($place === null) sendJson(['success' => false, 'data' => null, 'error' => 'Không tìm thấy quán đang hoạt động.'], 404);
    sendJson(['success' => true, 'data' => $place, 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Restaurant detail failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể tải chi tiết quán. Vui lòng thử lại.'], 500);
}
