<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respondDiscovery(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    respondDiscovery(['success' => false, 'data' => null, 'meta' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
}

try {
    $options = validateDiscoveryOptions($_GET);
} catch (InvalidArgumentException $error) {
    respondDiscovery(['success' => false, 'data' => null, 'meta' => null, 'error' => $error->getMessage()], 400);
}

try {
    $result = discoverDishes(openDatabase(), $options);
    respondDiscovery(['success' => true, 'data' => $result['data'], 'meta' => $result['meta'], 'error' => null]);
} catch (Throwable $error) {
    error_log('Dish discovery failed: ' . $error->getMessage());
    respondDiscovery(['success' => false, 'data' => null, 'meta' => null, 'error' => 'Không thể tải món ăn. Vui lòng thử lại.'], 500);
}
