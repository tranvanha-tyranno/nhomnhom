<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true)) {
        sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
    }
    $sessionKey = nomnomSessionKey();
    if ($method === 'POST') {
        $payload = readJsonObject();
        requireNomNomCsrf($payload);
        $payload = validateSavedRestaurant($payload);
    }
    $db = openDatabase();
    ensureSavedRestaurantTable($db);
    if ($method === 'POST') changeSavedRestaurant($db, $sessionKey, $payload);
    sendJson(['success' => true, 'data' => listSavedRestaurants($db, $sessionKey), 'csrfToken' => nomnomCsrfToken(), 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Saved restaurants failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể cập nhật quán đã lưu. Vui lòng thử lại.'], 500);
}
