<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/verified_seed.php';

function assertVerified(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$sourceDb = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nhomnhom.sqlite';
$tmpDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nhomnhom_verified_test_' . bin2hex(random_bytes(4)) . '.sqlite';
if (!copy($sourceDb, $tmpDb)) {
    throw new RuntimeException('Cannot copy SQLite DB for verified seed test.');
}

try {
    putenv('NHOMNHOM_DB_DRIVER=sqlite');
    putenv('NHOMNHOM_DB_PATH=' . $tmpDb);
    $db = openDatabase();

    $first = seedVerifiedRestaurants($db);
    assertVerified(true, $first['restaurants'] >= 20, 'Should seed at least 20 verified restaurants');
    assertVerified(true, $first['menuItems'] >= 60, 'Should seed menu items for verified restaurants');

    // Idempotency check
    $countBefore = (int) $db->query('SELECT COUNT(*) FROM restaurants WHERE is_demo = 0')->fetchColumn();
    $second = seedVerifiedRestaurants($db);
    $countAfter = (int) $db->query('SELECT COUNT(*) FROM restaurants WHERE is_demo = 0')->fetchColumn();
    assertVerified($countBefore, $countAfter, 'Verified restaurant seeder must be idempotent');
    assertVerified($first['restaurants'], $second['restaurants'], 'Second run should report same count');

    // Verified filter + FTS5 accent-insensitive search check
    $verifiedList = findRestaurants($db, validateSearchOptions('', 1, 50, 0, 'rating', true));
    assertVerified($countAfter, $verifiedList['pagination']['total'], 'verifiedOnly filter should return all verified restaurants');

    foreach ($verifiedList['data'] as $place) {
        assertVerified(false, $place['isDemo'], 'Verified place must have isDemo=false: ' . $place['name']);
        assertVerified(true, $place['lat'] >= 8.0 && $place['lat'] <= 23.5, 'Latitude must be inside Vietnam: ' . $place['name']);
        assertVerified(true, $place['lng'] >= 102.0 && $place['lng'] <= 110.0, 'Longitude must be inside Vietnam: ' . $place['name']);
        $url = buildDirectionsUrl((float) $place['lat'], (float) $place['lng'], 'driving');
        assertVerified(true, str_starts_with($url, 'https://www.google.com/maps/dir/?api=1&'), 'Directions URL must be valid Google Maps link');
    }

    $phoSearch = findRestaurants($db, validateSearchOptions('pho hoa pasteur', 1, 10, 0, 'relevance', true));
    assertVerified(true, $phoSearch['pagination']['total'] >= 1, 'FTS5 search must find Phở Hòa Pasteur without accents');

    $firstId = (int) $verifiedList['data'][0]['id'];
    $detail = findRestaurantDetail($db, $firstId);
    assertVerified(false, $detail['isDemo'], 'Restaurant detail must expose isDemo=false');
    assertVerified(true, $detail['menuCount'] >= 3, 'Verified restaurant detail must include menu items');

    fwrite(STDOUT, "PHP verified seed tests passed\n");
} finally {
    $db = null;
    gc_collect_cycles();
    @unlink($tmpDb);
}
