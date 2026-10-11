<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    $db = openDatabase(); $sessionKey = nomnomSessionKey();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') sendJson(['success' => true, 'data' => listNomNomBookings($db, $sessionKey), 'error' => null]);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
    $payload = readJsonObject(); requireNomNomCsrf($payload);
    sendJson(['success' => true, 'data' => createNomNomBooking($db, $sessionKey, validateBookingPayload($payload)), 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Booking request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể tạo đặt bàn.'], 500);
}
