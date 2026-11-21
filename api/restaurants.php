<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    respond(['success' => false, 'data' => null, 'pagination' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
}

try {
    $options = parseSearchOptions($_GET);
} catch (InvalidArgumentException $error) {
    respond(['success' => false, 'data' => null, 'pagination' => null, 'error' => $error->getMessage()], 400);
}

try {
    $result = findRestaurants(openDatabase(), $options);
    respond(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination'], 'error' => null]);
} catch (Throwable $error) {
    error_log('Restaurant search failed: ' . $error->getMessage());
    respond(['success' => false, 'data' => null, 'pagination' => null, 'error' => 'Không thể tải danh sách quán ăn. Vui lòng thử lại.'], 500);
}
