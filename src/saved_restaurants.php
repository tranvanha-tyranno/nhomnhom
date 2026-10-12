<?php
declare(strict_types=1);

function ensureSavedRestaurantTable(PDO $db): void
{
    $db->exec(isMysqlConnection($db)
        ? 'CREATE TABLE IF NOT EXISTS nomnom_saved_restaurants (
            session_key CHAR(64) NOT NULL, restaurant_id BIGINT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(session_key, restaurant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        : 'CREATE TABLE IF NOT EXISTS nomnom_saved_restaurants (
            session_key TEXT NOT NULL, restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(session_key, restaurant_id))');
}

function validateSavedRestaurant(array $payload): array
{
    if (!is_int($payload['restaurantId'] ?? null) || $payload['restaurantId'] < 1
        || !in_array($payload['action'] ?? null, ['save', 'remove'], true)) {
        throw new InvalidArgumentException('Quán ăn hoặc thao tác lưu không hợp lệ.');
    }
    return ['restaurantId' => $payload['restaurantId'], 'action' => $payload['action']];
}

function changeSavedRestaurant(PDO $db, string $sessionKey, array $payload): void
{
    $params = [':session' => $sessionKey, ':id' => $payload['restaurantId']];
    if ($payload['action'] === 'remove') {
        $db->prepare('DELETE FROM nomnom_saved_restaurants WHERE session_key = :session AND restaurant_id = :id')->execute($params);
        return;
    }
    $exists = $db->prepare("SELECT id FROM restaurants WHERE id = :id AND status = 'active'");
    $exists->execute([':id' => $payload['restaurantId']]);
    if (!$exists->fetchColumn()) throw new InvalidArgumentException('Quán không tồn tại hoặc đã ngừng hoạt động.');
    $saved = listSavedRestaurants($db, $sessionKey);
    if (in_array((string) $payload['restaurantId'], array_column($saved, 'id'), true)) return;
    if (count($saved) >= 100) throw new InvalidArgumentException('Bạn đã lưu 100 quán. Hãy bỏ lưu một quán trước.');
    $sql = 'INSERT INTO nomnom_saved_restaurants(session_key, restaurant_id) VALUES(:session, :id)';
    $sql .= isMysqlConnection($db) ? ' ON DUPLICATE KEY UPDATE restaurant_id = VALUES(restaurant_id)' : ' ON CONFLICT(session_key, restaurant_id) DO NOTHING';
    $db->prepare($sql)->execute($params);
}

function listSavedRestaurants(PDO $db, string $sessionKey): array
{
    $statement = $db->prepare('SELECT ' . restaurantSummaryColumns() . ", r.status FROM nomnom_saved_restaurants s
        JOIN restaurants r ON r.id = s.restaurant_id WHERE s.session_key = :session
        ORDER BY s.created_at DESC, r.id DESC LIMIT 100");
    $statement->execute([':session' => $sessionKey]);
    return array_map(static fn(array $row): array => mapRestaurantRow($row) + ['isActive' => $row['status'] === 'active'], $statement->fetchAll());
}
