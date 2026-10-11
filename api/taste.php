<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sendJson(['success' => false, 'data' => null, 'error' => 'Chỉ hỗ trợ POST.'], 405);
    $payload = readJsonObject(); requireNomNomCsrf($payload);
    $result = recordNomNomTaste(openDatabase(), nomnomSessionKey(), validateTastePayload($payload));
    sendJson(['success' => true, 'data' => $result, 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Taste request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể lưu lựa chọn món ăn.'], 500);
}
