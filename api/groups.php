<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

try {
    $db = openDatabase(); $sessionKey = nomnomSessionKey();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $code = strtoupper(trim((string) ($_GET['inviteCode'] ?? '')));
        if (preg_match('/^[A-F0-9]{8}$/', $code) !== 1) throw new InvalidArgumentException('Mã nhóm không hợp lệ.');
        $group = $db->prepare('SELECT id FROM nomnom_taste_groups WHERE invite_code=? AND status="active"'); $group->execute([$code]);
        $groupId = (int) $group->fetchColumn(); if (!$groupId) throw new InvalidArgumentException('Không tìm thấy nhóm.');
        sendJson(['success' => true, 'data' => groupSummary($db, $groupId, $code), 'error' => null]);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sendJson(['success' => false, 'data' => null, 'error' => 'Phương thức không được hỗ trợ.'], 405);
    $payload = readJsonObject(); requireNomNomCsrf($payload); $validated = validateGroupPayload($payload);
    $data = match ($validated['action']) {
        'create' => createNomNomGroup($db, $sessionKey, $validated['displayName']),
        'join' => joinNomNomGroup($db, $sessionKey, $validated['inviteCode'], $validated['displayName']),
        'vote' => recordNomNomGroupVote($db, $sessionKey, $validated),
    };
    sendJson(['success' => true, 'data' => $data, 'error' => null]);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'data' => null, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Group request failed: ' . $error->getMessage());
    sendJson(['success' => false, 'data' => null, 'error' => 'Không thể xử lý nhóm chọn món.'], 500);
}
