<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

function assertMerchant(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function assertMerchantThrows(callable $fn, string $message): void
{
    try {
        $fn();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException($message);
}

$sourceDb = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nhomnhom.sqlite';
$tmpDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nhomnhom_merchant_test_' . bin2hex(random_bytes(4)) . '.sqlite';
if (!copy($sourceDb, $tmpDb)) {
    throw new RuntimeException('Cannot copy SQLite DB for merchant test.');
}

try {
    putenv('NHOMNHOM_DB_DRIVER=sqlite');
    putenv('NHOMNHOM_DB_PATH=' . $tmpDb);
    $db = openDatabase();

    // 1. Registration role allowlist
    $merchantPayload = validateAuthPayload([
        'action' => 'register',
        'displayName' => 'Chủ Quán Bún Đậu',
        'email' => 'store.a@example.com',
        'password' => 'MatKhau!123',
        'role' => 'merchant',
    ]);
    assertMerchant('merchant', $merchantPayload['role'], 'Valid merchant role must be accepted');

    $customerPayload = validateAuthPayload([
        'action' => 'register',
        'displayName' => 'Khách Hàng A',
        'email' => 'guest.a@example.com',
        'password' => 'MatKhau!123',
    ]);
    assertMerchant('customer', $customerPayload['role'], 'Default role must be customer');

    assertMerchantThrows(
        fn() => validateAuthPayload([
            'action' => 'register',
            'displayName' => 'Hacker',
            'email' => 'admin.try@example.com',
            'password' => 'MatKhau!123',
            'role' => 'admin',
        ]),
        'Self-registration as admin must be rejected'
    );

    // 2. Create two merchant accounts
    $merchantA = createNomNomAccount($db, $merchantPayload);
    assertMerchant('merchant', $merchantA['role'], 'Created account must carry merchant role');

    $merchantB = createNomNomAccount($db, validateAuthPayload([
        'action' => 'register',
        'displayName' => 'Chủ Quán Khác',
        'email' => 'store.b@example.com',
        'password' => 'MatKhau!123',
        'role' => 'merchant',
    ]));

    // 3. Merchant A creates a new verified restaurant with GPS coordinates
    $created = createMerchantRestaurant($db, $merchantA['id'], [
        'name' => 'Quán Bún Đậu Mẹt Tre Q7',
        'description' => 'Bún đậu mắm tôm nóng giòn phục vụ sinh viên TDTU.',
        'address' => '19 Nguyễn Hữu Thọ, Phường Tân Phong, Quận 7, TP Hồ Chí Minh',
        'cityId' => 1,
        'areaName' => 'Quận 7',
        'latitude' => '10.7326',
        'longitude' => '106.6998',
        'priceLevel' => 1,
        'cuisineId' => 2,
        'tagIds' => [2, 5, 6],
    ]);
    $restaurantId = (int) $created['id'];
    assertMerchant(true, $restaurantId > 0, 'Created restaurant must return valid ID');
    assertMerchant(false, $created['isDemo'], 'Merchant restaurant must be verified (isDemo=false)');

    // Searchable immediately via FTS5
    $found = findRestaurants($db, validateSearchOptions('met tre q7', 1, 10, 0, 'relevance', true));
    assertMerchant(true, $found['pagination']['total'] >= 1, 'New merchant restaurant must be immediately indexed in FTS5');

    // 4. Merchant A adds a brand-new dish to their restaurant
    $categoryId = (int) $db->query('SELECT id FROM categories ORDER BY id ASC LIMIT 1')->fetchColumn();
    $dish = addMerchantDish($db, $merchantA['id'], [
        'restaurantId' => $restaurantId,
        'dishName' => 'Mẹt Bún Đậu Tá Lả Đặc Biệt',
        'description' => 'Đậu rán nóng, chả cốm, nem chua rán, thịt chân giò.',
        'cuisineId' => 2,
        'categoryId' => $categoryId,
        'priceVnd' => 65000,
        'isVegetarian' => 0,
        'spiceLevel' => 1,
    ]);
    assertMerchant(true, $dish['dishId'] > 0, 'Added dish must return valid dishId');

    $detail = findRestaurantDetail($db, $restaurantId);
    assertMerchant(1, $detail['menuCount'], 'Restaurant detail must include newly added dish');
    assertMerchant('Mẹt Bún Đậu Tá Lả Đặc Biệt', $detail['menu'][0]['name'], 'Menu must match added dish name');

    // Dish name must also be searchable via FTS5
    $foundByDish = findRestaurants($db, validateSearchOptions('ta la dac biet', 1, 10, 0, 'relevance', true));
    assertMerchant(true, $foundByDish['pagination']['total'] >= 1, 'New dish must be indexed into restaurant search');

    // 5. Security: Merchant B CANNOT add or delete dishes on Merchant A's restaurant
    assertMerchantThrows(
        fn() => addMerchantDish($db, $merchantB['id'], [
            'restaurantId' => $restaurantId,
            'dishName' => 'Món Trái Phép',
            'description' => 'Không được phép chèn vào quán người khác.',
            'cuisineId' => 2,
            'categoryId' => $categoryId,
            'priceVnd' => 50000,
            'isVegetarian' => 0,
            'spiceLevel' => 0,
        ]),
        'Merchant B must not add dishes to Merchant A restaurant'
    );
    assertMerchantThrows(
        fn() => removeMerchantMenuItem($db, $merchantB['id'], $restaurantId, $dish['dishId']),
        'Merchant B must not delete dishes from Merchant A restaurant'
    );

    // 6. Merchant A removes their own dish cleanly
    removeMerchantMenuItem($db, $merchantA['id'], $restaurantId, $dish['dishId']);
    $detailAfter = findRestaurantDetail($db, $restaurantId);
    assertMerchant(0, $detailAfter['menuCount'], 'Removed dish should disappear from restaurant menu');

    // 7. Merchant A can confirm a booking at their restaurant, Merchant B cannot
    $booking = createNomNomBooking($db, 'guest-session-key', [
        'restaurantId' => $restaurantId,
        'guestName' => 'Trần Minh',
        'email' => 'minh@example.com',
        'bookingDate' => date('Y-m-d', strtotime('+3 days')) . ' 19:00',
        'guestCount' => 4,
        'note' => 'Bàn thoáng mát',
    ]);
    assertMerchantThrows(
        fn() => updateMerchantBookingStatus($db, $merchantB['id'], (int) $booking['id'], 'confirmed'),
        'Merchant B must not manage bookings for Merchant A restaurant'
    );
    updateMerchantBookingStatus($db, $merchantA['id'], (int) $booking['id'], 'confirmed');
    $bookings = listMerchantBookings($db, $merchantA['id']);
    assertMerchant(1, count($bookings), 'Merchant A should see 1 incoming booking');
    assertMerchant('confirmed', $bookings[0]['status'], 'Booking status should be updated to confirmed');

    fwrite(STDOUT, "PHP merchant tests passed\n");
} finally {
    $db = null;
    gc_collect_cycles();
    @unlink($tmpDb);
}
