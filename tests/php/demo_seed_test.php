<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/demo_seed.php';

function assertSeed(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(file_get_contents(__DIR__ . '/../../database/migrations/001_catalog.sql'));
ensureNomNomFeatureTables($db);

$db->exec("
    INSERT INTO countries(id, code, name) VALUES (1, 'VN', 'Vietnam');
    INSERT INTO cities(id, country_id, name, latitude, longitude) VALUES (1, 1, 'HCM', 10.7758, 106.7019);
    INSERT INTO areas(id, city_id, name) VALUES (1, 1, 'Quận 1');
    INSERT INTO cuisines(id, name, description) VALUES (1, 'Việt Nam', 'Ẩm thực Việt');
    INSERT INTO categories(id, name) VALUES (1, 'Món chính');
");

$dishes = [
    [1, 'Bún bò Huế truyền thống', 0, 0],
    [2, 'Phở bò truyền thống', 0, 1],
    [3, 'Cơm chay truyền thống', 1, 2],
    [4, 'Bánh mì truyền thống', 0, 3],
    [5, 'Gỏi cuốn truyền thống', 0, 0],
    [6, 'Bún chả truyền thống', 0, 1],
    [7, 'Cơm tấm truyền thống', 0, 2],
    [8, 'Mì Quảng truyền thống', 0, 3],
    [9, 'Bánh xèo truyền thống', 0, 0],
    [10, 'Bún riêu truyền thống', 0, 1],
    [11, 'Cháo nấm truyền thống', 1, 2],
    [12, 'Bánh cuốn truyền thống', 0, 3],
    [13, 'Hủ tiếu truyền thống', 0, 0],
    [14, 'Bún thịt nướng truyền thống', 0, 1],
    [15, 'Cơm gà truyền thống', 0, 2],
];
$dishStmt = $db->prepare('INSERT INTO dishes(id, category_id, cuisine_id, name, description, is_vegetarian, spice_level) VALUES (?, 1, 1, ?, ?, ?, ?)');
foreach ($dishes as [$id, $name, $veg, $spice]) {
    $dishStmt->execute([$id, $name, 'Mô tả ' . $name, $veg, $spice]);
}

$restStmt = $db->prepare("INSERT INTO restaurants(id, area_id, name, slug, description, address, latitude, longitude, price_level, status, is_demo) VALUES (?, 1, ?, ?, 'Quán mẫu', 'Quận 1, TP HCM', 10.7758, 106.7019, ?, 'active', 1)");
for ($r = 1; $r <= 12; $r++) {
    $restStmt->execute([$r, 'Nhà hàng Mẫu ' . $r, 'nha-hang-mau-' . $r, (($r - 1) % 3) + 1]);
}

$menuStmt = $db->prepare('INSERT INTO menu_items(id, restaurant_id, dish_id, price_vnd, is_available) VALUES (?, ?, ?, ?, 1)');
for ($d = 1; $d <= 15; $d++) {
    $menuStmt->execute([$d, (($d - 1) % 12) + 1, $d, 35000 + $d * 5000]);
}

$passcode = 'NhomNhom@2026';
seedNomNomDemoAccounts($db, $passcode);
$summary = seedNomNomDemoAccounts($db, $passcode);

assertSeed($summary['accounts'] === 5, 'Should seed exactly 5 demo accounts');
assertSeed($summary['bookings'] === 11, 'Should seed 11 advance bookings across the 5 accounts');
assertSeed($summary['coupons'] === 3, 'Should seed 3 coupons');
assertSeed(count($summary['groups']) === 2, 'Should seed 2 voting groups');

$expectedAccounts = [
    'test@nhomnhom.local' => ['displayName' => 'Người dùng Test', 'vegetarianOnly' => false, 'maxPriceVnd' => 120000, 'travelMode' => 'driving', 'minBookings' => 3],
    'minh.anh@nhomnhom.vn' => ['displayName' => 'Minh Anh', 'vegetarianOnly' => false, 'maxPriceVnd' => 85000, 'travelMode' => 'walking', 'minBookings' => 2],
    'hoang.nam@nhomnhom.vn' => ['displayName' => 'Hoàng Nam', 'vegetarianOnly' => false, 'maxPriceVnd' => 150000, 'travelMode' => 'driving', 'minBookings' => 2],
    'thu.ha@nhomnhom.vn' => ['displayName' => 'Thu Hà', 'vegetarianOnly' => true, 'maxPriceVnd' => 70000, 'travelMode' => 'bicycling', 'minBookings' => 2],
    'quoc.bao@nhomnhom.vn' => ['displayName' => 'Quốc Bảo', 'vegetarianOnly' => false, 'maxPriceVnd' => 200000, 'travelMode' => 'transit', 'minBookings' => 2],
];

$totalAccounts = (int) $db->query("SELECT COUNT(*) FROM nomnom_accounts WHERE status='active'")->fetchColumn();
assertSeed($totalAccounts === 5, 'Idempotent seeding must keep exactly 5 active accounts');

foreach ($expectedAccounts as $email => $expected) {
    $account = loginNomNomAccount($db, validateAuthPayload([
        'action' => 'login',
        'email' => $email,
        'password' => $passcode,
    ]));
    assertSeed($account['displayName'] === $expected['displayName'], 'Display name mismatch for ' . $email);

    $identityKey = accountIdentityKey((int) $account['id']);
    assertSeed($identityKey === hash('sha256', 'nhomnhom:account:' . $account['id']), 'Identity key must match session.php formula');

    $profile = getNomNomProfile($db, $identityKey);
    assertSeed($profile['displayName'] === $expected['displayName'], 'Profile displayName mismatch for ' . $email);
    assertSeed($profile['vegetarianOnly'] === $expected['vegetarianOnly'], 'Profile vegetarianOnly mismatch for ' . $email);
    assertSeed($profile['maxPriceVnd'] === $expected['maxPriceVnd'], 'Profile maxPriceVnd mismatch for ' . $email);
    assertSeed($profile['travelMode'] === $expected['travelMode'], 'Profile travelMode mismatch for ' . $email);

    $favorites = listNomNomFavorites($db, $identityKey);
    assertSeed(count($favorites) >= 2, 'Account ' . $email . ' must have at least 2 favorite dishes');

    $bookings = listNomNomBookings($db, $identityKey);
    assertSeed(count($bookings) >= 2 && count($bookings) === $expected['minBookings'], 'Account ' . $email . ' booking count mismatch');
    $upcomingActive = array_filter($bookings, static fn(array $b): bool => in_array($b['status'], ['confirmed', 'pending'], true) && $b['bookingDate'] > '2026-11-21 00:00');
    assertSeed(count($upcomingActive) >= 2, 'Account ' . $email . ' must have at least 2 upcoming confirmed/pending bookings');
}

$cafeId = (int) $db->query("SELECT id FROM nomnom_taste_groups WHERE invite_code='CAFE2026'")->fetchColumn();
assertSeed($cafeId > 0, 'Group CAFE2026 must exist');
$cafeSummary = groupSummary($db, $cafeId, 'CAFE2026');
assertSeed($cafeSummary['memberCount'] === 4, 'CAFE2026 must have 4 members');
assertSeed($cafeSummary['matches'][0]['dishId'] === 1 && $cafeSummary['matches'][0]['unanimous'] === true, 'CAFE2026 must agree unanimously on dish 1');

$beefId = (int) $db->query("SELECT id FROM nomnom_taste_groups WHERE invite_code='BEEF1234'")->fetchColumn();
assertSeed($beefId > 0, 'Group BEEF1234 must exist');
$beefSummary = groupSummary($db, $beefId, 'BEEF1234');
assertSeed($beefSummary['memberCount'] === 2, 'BEEF1234 must have 2 members');
assertSeed($beefSummary['matches'][0]['dishId'] === 3 && $beefSummary['matches'][0]['unanimous'] === true, 'BEEF1234 must agree unanimously on dish 3');

$insights = nomNomInsights($db);
assertSeed(count($insights) > 0 && $insights[0]['likes'] > 0, 'Insights should reflect seeded likes and bookings');
$topDishes = nomNomTopDishes($db, 5);
assertSeed(count($topDishes) === 5 && $topDishes[0]['eloRating'] > 1200, 'Top dishes should reflect Elo wins from seeded likes');

fwrite(STDOUT, "PHP demo seed tests passed\n");
