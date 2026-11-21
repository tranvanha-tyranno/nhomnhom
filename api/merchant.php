<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

try {
    $db = openDatabase();
    $account = currentNomNomAccount($db);
    if (!$account) {
        sendJson(['success' => false, 'error' => 'Vui lòng đăng nhập tài khoản Cửa hàng.'], 401);
    }
    if (!isNomNomMerchant($account)) {
        sendJson(['success' => false, 'error' => 'Tài khoản này không có quyền Cửa hàng.'], 403);
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        sendJson([
            'success' => true,
            'csrfToken' => nomnomCsrfToken(),
            'account' => $account,
            'options' => merchantCatalogOptions($db),
            'restaurants' => listMerchantRestaurants($db, $account['id']),
            'bookings' => listMerchantBookings($db, $account['id']),
        ]);
    }

    if ($method === 'POST') {
        $payload = readJsonObject();
        requireNomNomCsrf($payload);
        $action = (string) ($payload['action'] ?? '');
        if ($action === 'create_restaurant') {
            $created = createMerchantRestaurant($db, $account['id'], $payload);
            sendJson(['success' => true, 'data' => $created], 201);
        }
        if ($action === 'add_dish') {
            $dish = addMerchantDish($db, $account['id'], $payload);
            sendJson(['success' => true, 'data' => $dish], 201);
        }
        if ($action === 'remove_dish') {
            removeMerchantMenuItem($db, $account['id'], (int) ($payload['restaurantId'] ?? 0), (int) ($payload['dishId'] ?? 0));
            sendJson(['success' => true]);
        }
        if ($action === 'booking_status') {
            updateMerchantBookingStatus($db, $account['id'], (int) ($payload['bookingId'] ?? 0), (string) ($payload['status'] ?? ''));
            sendJson(['success' => true]);
        }
        sendJson(['success' => false, 'error' => 'Hành động cửa hàng không hợp lệ.'], 400);
    }

    sendJson(['success' => false, 'error' => 'Phương thức không được hỗ trợ.'], 405);
} catch (InvalidArgumentException $error) {
    sendJson(['success' => false, 'error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Merchant API failed: ' . $error->getMessage());
    sendJson(['success' => false, 'error' => 'Không thể xử lý yêu cầu cửa hàng lúc này.'], 500);
}
