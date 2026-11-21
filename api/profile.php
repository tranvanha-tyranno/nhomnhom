<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    $sessionKey = nomnomIdentityKey();
    $db = openDatabase();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $profile = getNomNomProfile($db, $sessionKey);
        if ($profile['displayName'] === 'Bạn') $profile['displayName'] = (string) ($_SESSION['account_name'] ?? 'Bạn');
        sendJson(['success' => true, 'data' => $profile, 'csrfToken' => nomnomCsrfToken(), 'error' => null]);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
    $payload = readJsonObject(); requireNomNomCsrf($payload);
    sendJson(['success' => true, 'data' => saveNomNomProfile($db, $sessionKey, validateProfilePayload($payload)), 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Profile request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể lưu hồ sơ khẩu vị.'], 500);
}
