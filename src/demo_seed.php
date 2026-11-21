<?php
declare(strict_types=1);

function accountIdentityKey(int $accountId): string
{
    return hash('sha256', 'nhomnhom:account:' . $accountId);
}

function demoAccountBlueprints(): array
{
    return [
        [
            'email' => 'test@nhomnhom.local',
            'displayName' => 'Người dùng Test',
            'profile' => ['vegetarianOnly' => false, 'maxPriceVnd' => 120000, 'travelMode' => 'driving'],
            'likes' => [1, 2, 6, 7],
            'skips' => [4],
        ],
        [
            'email' => 'minh.anh@nhomnhom.vn',
            'displayName' => 'Minh Anh',
            'profile' => ['vegetarianOnly' => false, 'maxPriceVnd' => 85000, 'travelMode' => 'walking'],
            'likes' => [2, 5, 9, 10],
            'skips' => [8],
        ],
        [
            'email' => 'hoang.nam@nhomnhom.vn',
            'displayName' => 'Hoàng Nam',
            'profile' => ['vegetarianOnly' => false, 'maxPriceVnd' => 150000, 'travelMode' => 'driving'],
            'likes' => [1, 4, 7, 8, 14],
            'skips' => [3],
        ],
        [
            'email' => 'thu.ha@nhomnhom.vn',
            'displayName' => 'Thu Hà',
            'profile' => ['vegetarianOnly' => true, 'maxPriceVnd' => 70000, 'travelMode' => 'bicycling'],
            'likes' => [3, 11],
            'skips' => [1],
        ],
        [
            'email' => 'quoc.bao@nhomnhom.vn',
            'displayName' => 'Quốc Bảo',
            'profile' => ['vegetarianOnly' => false, 'maxPriceVnd' => 200000, 'travelMode' => 'transit'],
            'likes' => [1, 6, 12, 13, 15],
            'skips' => [11],
        ],
    ];
}

function demoCouponBlueprints(): array
{
    return [
        ['restaurantId' => 1, 'code' => 'NHOM10', 'discountPercent' => 10, 'usedCount' => 1],
        ['restaurantId' => 3, 'code' => 'CHAY20', 'discountPercent' => 20, 'usedCount' => 1],
        ['restaurantId' => 8, 'code' => 'BANHMI15', 'discountPercent' => 15, 'usedCount' => 1],
    ];
}

function demoBookingBlueprints(): array
{
    return [
        'test@nhomnhom.local' => [
            [1, '2026-12-10 19:00', 4, 'confirmed', 'NHOM10', 'Bàn gần cửa sổ, 4 người lớn'],
            [2, '2026-12-15 12:30', 2, 'pending', null, 'Hẹn ăn trưa nhanh'],
            [7, '2026-12-01 18:30', 3, 'cancelled', null, 'Đổi lịch sang tuần sau'],
        ],
        'minh.anh@nhomnhom.vn' => [
            [5, '2026-12-11 18:30', 2, 'confirmed', null, 'Chỗ ngồi yên tĩnh'],
            [9, '2026-12-18 19:30', 6, 'pending', null, 'Liên hoan nhóm nhỏ'],
        ],
        'hoang.nam@nhomnhom.vn' => [
            [8, '2026-12-12 12:00', 4, 'confirmed', 'BANHMI15', 'Thêm ớt tươi và rau sống'],
            [6, '2026-12-20 19:00', 3, 'pending', null, 'Đặt trước bàn tầng 2'],
        ],
        'thu.ha@nhomnhom.vn' => [
            [3, '2026-12-14 18:00', 2, 'confirmed', 'CHAY20', 'Thực đơn thuần chay, ít dầu'],
            [11, '2026-12-22 11:30', 4, 'pending', null, 'Bàn gia đình buổi trưa'],
        ],
        'quoc.bao@nhomnhom.vn' => [
            [12, '2026-12-16 08:30', 5, 'confirmed', null, 'Ăn sáng cuối tuần cả nhóm'],
            [10, '2026-12-24 19:00', 2, 'pending', null, 'Không hành lá cho 1 suất'],
        ],
    ];
}

function demoGroupBlueprints(): array
{
    return [
        [
            'code' => 'CAFE2026',
            'owner' => 'test@nhomnhom.local',
            'members' => ['test@nhomnhom.local', 'minh.anh@nhomnhom.vn', 'hoang.nam@nhomnhom.vn', 'quoc.bao@nhomnhom.vn'],
            'votes' => [
                ['test@nhomnhom.local', 1, 'like'],
                ['minh.anh@nhomnhom.vn', 1, 'like'],
                ['hoang.nam@nhomnhom.vn', 1, 'like'],
                ['quoc.bao@nhomnhom.vn', 1, 'like'],
                ['test@nhomnhom.local', 2, 'like'],
                ['minh.anh@nhomnhom.vn', 2, 'like'],
                ['hoang.nam@nhomnhom.vn', 2, 'like'],
                ['quoc.bao@nhomnhom.vn', 2, 'skip'],
                ['test@nhomnhom.local', 6, 'like'],
                ['quoc.bao@nhomnhom.vn', 6, 'like'],
            ],
        ],
        [
            'code' => 'BEEF1234',
            'owner' => 'minh.anh@nhomnhom.vn',
            'members' => ['minh.anh@nhomnhom.vn', 'thu.ha@nhomnhom.vn'],
            'votes' => [
                ['minh.anh@nhomnhom.vn', 3, 'like'],
                ['thu.ha@nhomnhom.vn', 3, 'like'],
                ['minh.anh@nhomnhom.vn', 11, 'like'],
                ['thu.ha@nhomnhom.vn', 11, 'like'],
            ],
        ],
    ];
}

function upsertDemoAccount(PDO $db, string $email, string $displayName, string $passwordHash, string $role = 'customer'): array
{
    $select = $db->prepare('SELECT id FROM nomnom_accounts WHERE email = ?');
    $select->execute([$email]);
    $existingId = (int) $select->fetchColumn();
    if ($existingId > 0) {
        $db->prepare("UPDATE nomnom_accounts SET password_hash=?, display_name=?, role=?, status='active' WHERE id=?")
            ->execute([$passwordHash, $displayName, $role, $existingId]);
        $accountId = $existingId;
    } else {
        $db->prepare("INSERT INTO nomnom_accounts(email, password_hash, display_name, role, status) VALUES (?, ?, ?, ?, 'active')")
            ->execute([$email, $passwordHash, $displayName, $role]);
        $accountId = (int) $db->lastInsertId();
    }
    return ['id' => $accountId, 'email' => $email, 'displayName' => $displayName, 'role' => $role, 'identityKey' => accountIdentityKey($accountId)];
}

function upsertDemoCoupons(PDO $db): int
{
    $coupons = demoCouponBlueprints();
    $sql = isMysqlConnection($db)
        ? "INSERT INTO nomnom_coupons(restaurant_id, code, discount_percent, max_uses, used_count, expires_at, status) VALUES (?, ?, ?, 100, ?, '2027-12-31 23:59:00', 'active') ON DUPLICATE KEY UPDATE restaurant_id=VALUES(restaurant_id), discount_percent=VALUES(discount_percent), used_count=VALUES(used_count), expires_at=VALUES(expires_at), status='active'"
        : "INSERT INTO nomnom_coupons(restaurant_id, code, discount_percent, max_uses, used_count, expires_at, status) VALUES (?, ?, ?, 100, ?, '2027-12-31 23:59', 'active') ON CONFLICT(code) DO UPDATE SET restaurant_id=excluded.restaurant_id, discount_percent=excluded.discount_percent, used_count=excluded.used_count, expires_at=excluded.expires_at, status='active'";
    $stmt = $db->prepare($sql);
    foreach ($coupons as $coupon) {
        $stmt->execute([$coupon['restaurantId'], $coupon['code'], $coupon['discountPercent'], $coupon['usedCount']]);
    }
    return count($coupons);
}

function seedDemoTasteActions(PDO $db, array $accounts, array $blueprints): array
{
    $identityKeys = array_values(array_map(static fn(array $acc): string => $acc['identityKey'], $accounts));
    $placeholders = implode(',', array_fill(0, count($identityKeys), '?'));
    $db->prepare("DELETE FROM nomnom_taste_actions WHERE session_key IN ({$placeholders})")->execute($identityKeys);

    $dishIds = [];
    foreach ($blueprints as $item) {
        foreach (array_merge($item['likes'], $item['skips']) as $dishId) {
            $dishIds[$dishId] = true;
        }
    }
    $touchedDishes = array_keys($dishIds);
    if ($touchedDishes !== []) {
        $dishPlaceholders = implode(',', array_fill(0, count($touchedDishes), '?'));
        $db->prepare("DELETE FROM nomnom_dish_elo WHERE dish_id IN ({$dishPlaceholders})")->execute($touchedDishes);
    }

    $likesCount = 0;
    $skipsCount = 0;
    foreach ($blueprints as $item) {
        $key = $accounts[$item['email']]['identityKey'];
        foreach ($item['likes'] as $dishId) {
            recordNomNomTaste($db, $key, ['dishId' => $dishId, 'action' => 'like']);
            $likesCount++;
        }
        foreach ($item['skips'] as $dishId) {
            recordNomNomTaste($db, $key, ['dishId' => $dishId, 'action' => 'skip']);
            $skipsCount++;
        }
    }
    return ['likes' => $likesCount, 'skips' => $skipsCount];
}

function seedDemoBookings(PDO $db, array $accounts): int
{
    $identityKeys = array_values(array_map(static fn(array $acc): string => $acc['identityKey'], $accounts));
    $placeholders = implode(',', array_fill(0, count($identityKeys), '?'));
    $db->prepare("DELETE FROM nomnom_bookings WHERE session_key IN ({$placeholders})")->execute($identityKeys);

    $insert = $db->prepare('INSERT INTO nomnom_bookings(session_key, restaurant_id, guest_name, email, booking_date, guest_count, status, coupon_code, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $count = 0;
    foreach (demoBookingBlueprints() as $email => $rows) {
        $acc = $accounts[$email];
        foreach ($rows as [$restaurantId, $date, $guests, $status, $coupon, $note]) {
            $insert->execute([$acc['identityKey'], $restaurantId, $acc['displayName'], $email, $date, $guests, $status, $coupon, $note]);
            $count++;
        }
    }
    return $count;
}

function seedDemoGroups(PDO $db, array $accounts): array
{
    $groups = demoGroupBlueprints();
    $codes = array_values(array_map(static fn(array $g): string => $g['code'], $groups));
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $existingStmt = $db->prepare("SELECT id FROM nomnom_taste_groups WHERE invite_code IN ({$placeholders})");
    $existingStmt->execute($codes);
    $existingIds = array_values(array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN)));
    if ($existingIds !== []) {
        $idPlaceholders = implode(',', array_fill(0, count($existingIds), '?'));
        $db->prepare("DELETE FROM nomnom_group_votes WHERE group_id IN ({$idPlaceholders})")->execute($existingIds);
        $db->prepare("DELETE FROM nomnom_group_members WHERE group_id IN ({$idPlaceholders})")->execute($existingIds);
        $db->prepare("DELETE FROM nomnom_taste_groups WHERE id IN ({$idPlaceholders})")->execute($existingIds);
    }

    $insertGroup = $db->prepare("INSERT INTO nomnom_taste_groups(owner_key, invite_code, status) VALUES (?, ?, 'active')");
    $insertMember = $db->prepare('INSERT INTO nomnom_group_members(group_id, session_key, display_name) VALUES (?, ?, ?)');
    $insertVote = $db->prepare('INSERT INTO nomnom_group_votes(group_id, session_key, dish_id, vote) VALUES (?, ?, ?, ?)');

    foreach ($groups as $group) {
        $insertGroup->execute([$accounts[$group['owner']]['identityKey'], $group['code']]);
        $groupId = (int) $db->lastInsertId();
        foreach ($group['members'] as $email) {
            $insertMember->execute([$groupId, $accounts[$email]['identityKey'], $accounts[$email]['displayName']]);
        }
        foreach ($group['votes'] as [$email, $dishId, $vote]) {
            $insertVote->execute([$groupId, $accounts[$email]['identityKey'], $dishId, $vote]);
        }
    }
    return $codes;
}

function seedNomNomDemoAccounts(PDO $db, string $passcode): array
{
    if (strlen($passcode) < 8 || strlen($passcode) > 200) {
        throw new InvalidArgumentException('Mật khẩu demo phải từ 8 đến 200 ký tự.');
    }
    $blueprints = demoAccountBlueprints();
    $passwordHash = password_hash($passcode, PASSWORD_DEFAULT);
    $ownsTx = !$db->inTransaction();
    if ($ownsTx) {
        $db->beginTransaction();
    }
    try {
        $accounts = [];
        foreach ($blueprints as $item) {
            $account = upsertDemoAccount($db, $item['email'], $item['displayName'], $passwordHash);
            $accounts[$item['email']] = $account;
            saveNomNomProfile($db, $account['identityKey'], array_merge(['displayName' => $item['displayName']], $item['profile']));
        }
        $coupons = upsertDemoCoupons($db);
        $tasteStats = seedDemoTasteActions($db, $accounts, $blueprints);
        $bookings = seedDemoBookings($db, $accounts);
        $groups = seedDemoGroups($db, $accounts);
        if ($ownsTx) {
            $db->commit();
        }
        return [
            'accounts' => count($accounts),
            'coupons' => $coupons,
            'likes' => $tasteStats['likes'],
            'skips' => $tasteStats['skips'],
            'bookings' => $bookings,
            'groups' => $groups,
        ];
    } catch (Throwable $error) {
        if ($ownsTx && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}
