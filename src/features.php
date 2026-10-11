<?php
declare(strict_types=1);

function validateProfilePayload(array $input): array
{
    $name = trim((string) ($input['displayName'] ?? 'Bạn'));
    $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($name === '' || $nameLength > 80 || preg_match('/[\x00-\x1F]/', $name) === 1) {
        throw new InvalidArgumentException('Tên hiển thị phải từ 1 đến 80 ký tự.');
    }
    $maxPrice = parseFeatureInt($input['maxPriceVnd'] ?? 0, 0, 100000000, 'Ngân sách');
    $vegetarian = filterFeatureBool($input['vegetarianOnly'] ?? false, 'Bộ lọc món chay');
    $travelMode = (string) ($input['travelMode'] ?? 'walking');
    if (!in_array($travelMode, ['driving', 'walking', 'bicycling', 'transit'], true)) {
        throw new InvalidArgumentException('Phương tiện không hợp lệ.');
    }
    return ['displayName' => $name, 'vegetarianOnly' => $vegetarian, 'maxPriceVnd' => $maxPrice, 'travelMode' => $travelMode];
}

function validateTastePayload(array $input): array
{
    $dishId = parseFeatureInt($input['dishId'] ?? 0, 1, 2147483647, 'Mã món');
    $action = (string) ($input['action'] ?? '');
    if (!in_array($action, ['like', 'skip'], true)) {
        throw new InvalidArgumentException('Hành động món ăn không hợp lệ.');
    }
    return ['dishId' => $dishId, 'action' => $action];
}

function validateBookingPayload(array $input): array
{
    $restaurantId = parseFeatureInt($input['restaurantId'] ?? 0, 1, 2147483647, 'Mã quán');
    $guestName = trim((string) ($input['guestName'] ?? ''));
    if ($guestName === '' || strlen($guestName) > 100) {
        throw new InvalidArgumentException('Tên người đặt không hợp lệ.');
    }
    $email = trim((string) ($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        throw new InvalidArgumentException('Email không hợp lệ.');
    }
    $bookingDate = trim((string) ($input['bookingDate'] ?? ''));
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $bookingDate);
    if (!$date || $date->format('Y-m-d H:i') !== $bookingDate || $date < new DateTimeImmutable('now')) {
        throw new InvalidArgumentException('Thời gian đặt bàn phải hợp lệ và ở tương lai.');
    }
    $guestCount = parseFeatureInt($input['guestCount'] ?? 0, 1, 20, 'Số người');
    $couponCode = strtoupper(trim((string) ($input['couponCode'] ?? '')));
    if ($couponCode !== '' && preg_match('/^[A-Z0-9_-]{3,32}$/', $couponCode) !== 1) {
        throw new InvalidArgumentException('Mã coupon không hợp lệ.');
    }
    $note = trim((string) ($input['note'] ?? ''));
    if (strlen($note) > 500) {
        throw new InvalidArgumentException('Ghi chú tối đa 500 ký tự.');
    }
    return compact('restaurantId', 'guestName', 'email', 'bookingDate', 'guestCount', 'couponCode', 'note');
}

function validateGroupPayload(array $input): array
{
    $action = (string) ($input['action'] ?? '');
    if (!in_array($action, ['create', 'join', 'vote'], true)) {
        throw new InvalidArgumentException('Hành động nhóm không hợp lệ.');
    }
    $name = trim((string) ($input['displayName'] ?? 'Bạn'));
    if ($name === '' || strlen($name) > 80) {
        throw new InvalidArgumentException('Tên thành viên không hợp lệ.');
    }
    $code = strtoupper(trim((string) ($input['inviteCode'] ?? '')));
    if (in_array($action, ['join', 'vote'], true) && preg_match('/^[A-F0-9]{8}$/', $code) !== 1) {
        throw new InvalidArgumentException('Mã nhóm không hợp lệ.');
    }
    $result = ['action' => $action, 'displayName' => $name, 'inviteCode' => $code];
    if ($action === 'vote') {
        $result['dishId'] = parseFeatureInt($input['dishId'] ?? 0, 1, 2147483647, 'Mã món');
        $vote = (string) ($input['vote'] ?? '');
        if (!in_array($vote, ['like', 'skip'], true)) {
            throw new InvalidArgumentException('Lựa chọn nhóm không hợp lệ.');
        }
        $result['vote'] = $vote;
    }
    return $result;
}

function parseFeatureInt(mixed $value, int $min, int $max, string $label): int
{
    if (is_int($value)) {
        $number = $value;
    } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
        $number = (int) $value;
    } else {
        throw new InvalidArgumentException($label . ' không hợp lệ.');
    }
    if ($number < $min || $number > $max) {
        throw new InvalidArgumentException($label . ' nằm ngoài giới hạn.');
    }
    return $number;
}

function filterFeatureBool(mixed $value, string $label): bool
{
    if (is_bool($value)) return $value;
    if ($value === 0 || $value === '0') return false;
    if ($value === 1 || $value === '1') return true;
    throw new InvalidArgumentException($label . ' không hợp lệ.');
}

function getNomNomProfile(PDO $db, string $sessionKey): array
{
    $statement = $db->prepare('SELECT display_name AS displayName, vegetarian_only AS vegetarianOnly, max_price_vnd AS maxPriceVnd, travel_mode AS travelMode FROM nomnom_profiles WHERE session_key = ?');
    $statement->execute([$sessionKey]);
    $profile = $statement->fetch();
    return $profile ? normalizeProfileRow($profile) : ['displayName' => 'Bạn', 'vegetarianOnly' => false, 'maxPriceVnd' => 0, 'travelMode' => 'walking'];
}

function saveNomNomProfile(PDO $db, string $sessionKey, array $profile): array
{
    $sql = isMysqlConnection($db)
        ? "INSERT INTO nomnom_profiles(session_key, display_name, vegetarian_only, max_price_vnd, travel_mode) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), vegetarian_only=VALUES(vegetarian_only), max_price_vnd=VALUES(max_price_vnd), travel_mode=VALUES(travel_mode), updated_at=CURRENT_TIMESTAMP"
        : "INSERT INTO nomnom_profiles(session_key, display_name, vegetarian_only, max_price_vnd, travel_mode) VALUES (?, ?, ?, ?, ?) ON CONFLICT(session_key) DO UPDATE SET display_name=excluded.display_name, vegetarian_only=excluded.vegetarian_only, max_price_vnd=excluded.max_price_vnd, travel_mode=excluded.travel_mode, updated_at=CURRENT_TIMESTAMP";
    $statement = $db->prepare($sql);
    $statement->execute([$sessionKey, $profile['displayName'], $profile['vegetarianOnly'] ? 1 : 0, $profile['maxPriceVnd'], $profile['travelMode']]);
    return $profile;
}

function normalizeProfileRow(array $row): array
{
    return ['displayName' => (string) $row['displayName'], 'vegetarianOnly' => (bool) $row['vegetarianOnly'], 'maxPriceVnd' => (int) $row['maxPriceVnd'], 'travelMode' => (string) $row['travelMode']];
}

function recordNomNomTaste(PDO $db, string $sessionKey, array $taste): array
{
    $exists = $db->prepare('SELECT id FROM dishes WHERE id = ?');
    $exists->execute([$taste['dishId']]);
    if (!$exists->fetchColumn()) throw new InvalidArgumentException('Món ăn không tồn tại.');
    $sql = isMysqlConnection($db)
        ? "INSERT INTO nomnom_taste_actions(session_key, dish_id, action) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE action=VALUES(action), created_at=CURRENT_TIMESTAMP"
        : "INSERT INTO nomnom_taste_actions(session_key, dish_id, action) VALUES (?, ?, ?) ON CONFLICT(session_key, dish_id) DO UPDATE SET action=excluded.action, created_at=CURRENT_TIMESTAMP";
    $statement = $db->prepare($sql);
    $statement->execute([$sessionKey, $taste['dishId'], $taste['action']]);
    updateDishElo($db, $taste['dishId'], $taste['action']);
    return ['dishId' => $taste['dishId'], 'action' => $taste['action']];
}

function updateDishElo(PDO $db, int $dishId, string $action): void
{
    $db->prepare(isMysqlConnection($db) ? 'INSERT IGNORE INTO nomnom_dish_elo(dish_id) VALUES (?)' : 'INSERT OR IGNORE INTO nomnom_dish_elo(dish_id) VALUES (?)')->execute([$dishId]);
    $change = $action === 'like' ? 8 : -3;
    $column = $action === 'like' ? 'wins' : 'losses';
    $ratingExpression = isMysqlConnection($db) ? 'LEAST(2400, GREATEST(400, rating + ?))' : 'MIN(2400, MAX(400, rating + ?))';
    $db->prepare("UPDATE nomnom_dish_elo SET rating={$ratingExpression}, {$column}={$column}+1, updated_at=CURRENT_TIMESTAMP WHERE dish_id=?")->execute([$change, $dishId]);
}

function createNomNomGroup(PDO $db, string $sessionKey, string $displayName): array
{
    do {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $check = $db->prepare('SELECT 1 FROM nomnom_taste_groups WHERE invite_code=?');
        $check->execute([$code]);
    } while ($check->fetchColumn());
    $db->prepare('INSERT INTO nomnom_taste_groups(owner_key, invite_code) VALUES (?, ?)')->execute([$sessionKey, $code]);
    $groupId = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO nomnom_group_members(group_id, session_key, display_name) VALUES (?, ?, ?)')->execute([$groupId, $sessionKey, $displayName]);
    return ['inviteCode' => $code, 'memberCount' => 1];
}

function joinNomNomGroup(PDO $db, string $sessionKey, string $code, string $displayName): array
{
    $group = $db->prepare('SELECT id FROM nomnom_taste_groups WHERE invite_code=? AND status="active"');
    $group->execute([$code]);
    $groupId = (int) $group->fetchColumn();
    if (!$groupId) throw new InvalidArgumentException('Không tìm thấy nhóm đang hoạt động.');
    $sql = isMysqlConnection($db)
        ? 'INSERT INTO nomnom_group_members(group_id, session_key, display_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name)'
        : 'INSERT INTO nomnom_group_members(group_id, session_key, display_name) VALUES (?, ?, ?) ON CONFLICT(group_id, session_key) DO UPDATE SET display_name=excluded.display_name';
    $db->prepare($sql)->execute([$groupId, $sessionKey, $displayName]);
    return ['inviteCode' => $code, 'memberCount' => groupMemberCount($db, $groupId)];
}

function groupMemberCount(PDO $db, int $groupId): int
{
    $statement = $db->prepare('SELECT COUNT(*) FROM nomnom_group_members WHERE group_id=?');
    $statement->execute([$groupId]);
    return (int) $statement->fetchColumn();
}

function recordNomNomGroupVote(PDO $db, string $sessionKey, array $payload): array
{
    $group = $db->prepare('SELECT id FROM nomnom_taste_groups WHERE invite_code=? AND status="active"');
    $group->execute([$payload['inviteCode']]);
    $groupId = (int) $group->fetchColumn();
    if (!$groupId) throw new InvalidArgumentException('Không tìm thấy nhóm đang hoạt động.');
    $member = $db->prepare('SELECT 1 FROM nomnom_group_members WHERE group_id=? AND session_key=?');
    $member->execute([$groupId, $sessionKey]);
    if (!$member->fetchColumn()) throw new InvalidArgumentException('Bạn chưa tham gia nhóm này.');
    $dish = $db->prepare('SELECT 1 FROM dishes WHERE id=?');
    $dish->execute([$payload['dishId']]);
    if (!$dish->fetchColumn()) throw new InvalidArgumentException('Món ăn không tồn tại.');
    $sql = isMysqlConnection($db)
        ? "INSERT INTO nomnom_group_votes(group_id, session_key, dish_id, vote) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote), created_at=CURRENT_TIMESTAMP"
        : "INSERT INTO nomnom_group_votes(group_id, session_key, dish_id, vote) VALUES (?, ?, ?, ?) ON CONFLICT(group_id, session_key, dish_id) DO UPDATE SET vote=excluded.vote, created_at=CURRENT_TIMESTAMP";
    $statement = $db->prepare($sql);
    $statement->execute([$groupId, $sessionKey, $payload['dishId'], $payload['vote']]);
    return groupSummary($db, $groupId, $payload['inviteCode']);
}

function groupSummary(PDO $db, int $groupId, string $code): array
{
    $members = groupMemberCount($db, $groupId);
    $statement = $db->prepare("SELECT d.id, d.name, d.description, COUNT(CASE WHEN v.vote='like' THEN 1 END) AS likes,
        COUNT(v.dish_id) AS votes FROM dishes d JOIN nomnom_group_votes v ON v.dish_id=d.id
        WHERE v.group_id=? GROUP BY d.id ORDER BY likes DESC, votes DESC, d.id LIMIT 20");
    $statement->execute([$groupId]);
    $matches = array_map(static fn(array $row): array => [
        'dishId' => (int) $row['id'], 'name' => (string) $row['name'], 'description' => (string) $row['description'],
        'likes' => (int) $row['likes'], 'votes' => (int) $row['votes'], 'unanimous' => $members > 0 && (int) $row['likes'] === $members,
    ], $statement->fetchAll());
    return ['inviteCode' => $code, 'memberCount' => $members, 'matches' => $matches];
}

function createNomNomBooking(PDO $db, string $sessionKey, array $booking): array
{
    $restaurant = $db->prepare("SELECT id, name FROM restaurants WHERE id=? AND status='active'");
    $restaurant->execute([$booking['restaurantId']]);
    $restaurantRow = $restaurant->fetch();
    if (!$restaurantRow) throw new InvalidArgumentException('Nhà hàng không tồn tại hoặc đã đóng cửa.');
    $couponCode = $booking['couponCode'] !== '' ? $booking['couponCode'] : null;
    $db->beginTransaction();
    try {
        if ($couponCode !== null) {
            $todayExpression = isMysqlConnection($db) ? 'CURRENT_DATE' : "date('now')";
            $coupon = $db->prepare("SELECT id FROM nomnom_coupons WHERE code=? AND restaurant_id=? AND status='active' AND used_count < max_uses AND (expires_at IS NULL OR expires_at >= {$todayExpression})");
            $coupon->execute([$couponCode, $booking['restaurantId']]);
            if (!$coupon->fetchColumn()) throw new InvalidArgumentException('Coupon không hợp lệ hoặc đã hết lượt.');
            $db->prepare('UPDATE nomnom_coupons SET used_count=used_count+1 WHERE code=?')->execute([$couponCode]);
        }
        $statement = $db->prepare('INSERT INTO nomnom_bookings(session_key, restaurant_id, guest_name, email, booking_date, guest_count, coupon_code, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$sessionKey, $booking['restaurantId'], $booking['guestName'], $booking['email'], $booking['bookingDate'], $booking['guestCount'], $couponCode, $booking['note']]);
        $id = (int) $db->lastInsertId();
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    return ['id' => $id, 'restaurantId' => $booking['restaurantId'], 'restaurantName' => $restaurantRow['name'], 'status' => 'pending', 'bookingDate' => $booking['bookingDate'], 'guestCount' => $booking['guestCount']];
}

function listNomNomBookings(PDO $db, string $sessionKey): array
{
    $statement = $db->prepare('SELECT b.id, b.restaurant_id AS restaurantId, r.name AS restaurantName, b.booking_date AS bookingDate, b.guest_count AS guestCount, b.status, b.coupon_code AS couponCode FROM nomnom_bookings b JOIN restaurants r ON r.id=b.restaurant_id WHERE b.session_key=? ORDER BY b.booking_date DESC LIMIT 50');
    $statement->execute([$sessionKey]);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id']; $row['restaurantId'] = (int) $row['restaurantId']; $row['guestCount'] = (int) $row['guestCount']; return $row;
    }, $statement->fetchAll());
}

function nomNomInsights(PDO $db): array
{
    $statement = $db->query("SELECT r.id, r.name, r.address, r.latitude AS lat, r.longitude AS lng,
        (SELECT COUNT(*) FROM nomnom_taste_actions ta JOIN menu_items mi ON mi.dish_id=ta.dish_id WHERE mi.restaurant_id=r.id AND ta.action='like') AS likes,
        (SELECT COUNT(*) FROM nomnom_bookings b WHERE b.restaurant_id=r.id AND b.status!='cancelled') AS bookings
        FROM restaurants r WHERE r.status='active' ORDER BY likes DESC, bookings DESC, r.id LIMIT 100");
    return array_map(static function (array $row): array {
        $row['id']=(int)$row['id']; $row['lat']=(float)$row['lat']; $row['lng']=(float)$row['lng']; $row['likes']=(int)$row['likes']; $row['bookings']=(int)$row['bookings']; $row['score']=$row['likes']*3+$row['bookings']*5; return $row;
    }, $statement->fetchAll());
}
