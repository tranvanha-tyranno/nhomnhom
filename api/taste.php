<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    $db = openDatabase();
    $identityKey = nomnomIdentityKey();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        sendJson(['success' => true, 'data' => listNomNomFavorites($db, $identityKey), 'csrfToken' => nomnomCsrfToken(), 'error' => null]);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sendJson(['success' => false, 'data' => null, 'error' => 'Chỉ hỗ trợ POST.'], 405);
    $payload = readJsonObject(); requireNomNomCsrf($payload);
    if (($payload['action'] ?? '') === 'remove') {
        $dishId = parseFeatureInt($payload['dishId'] ?? 0, 1, 2147483647, 'Mã món');
        removeNomNomFavorite($db, $identityKey, $dishId);
        sendJson(['success' => true, 'data' => ['dishId' => $dishId], 'error' => null]);
    }
    $result = recordNomNomTaste($db, $identityKey, validateTastePayload($payload));
    sendJson(['success' => true, 'data' => $result, 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Taste request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể lưu lựa chọn món ăn.'], 500);
}
