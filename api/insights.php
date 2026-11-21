<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') sendJson(['success' => false, 'data' => null, 'error' => 'Chỉ hỗ trợ GET.'], 405);
    $db = openDatabase();
    sendJson([
        'success' => true,
        'data' => nomNomInsights($db),
        'topDishes' => nomNomTopDishes($db, 8),
        'error' => null,
    ]);
} catch (Throwable $error) {
    error_log('Insights request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể tải dữ liệu xu hướng.'], 500);
}
