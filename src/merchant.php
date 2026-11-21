<?php
declare(strict_types=1);

function merchantCatalogOptions(PDO $db): array
{
    return [
        'cities' => $db->query('SELECT id, name, latitude, longitude FROM cities ORDER BY id ASC')->fetchAll(),
        'cuisines' => $db->query('SELECT id, name FROM cuisines ORDER BY id ASC')->fetchAll(),
        'categories' => $db->query('SELECT id, name FROM categories ORDER BY id ASC')->fetchAll(),
        'tags' => $db->query('SELECT id, name FROM tags ORDER BY id ASC')->fetchAll(),
    ];
}

function merchantOwnsRestaurant(PDO $db, int $accountId, int $restaurantId): bool
{
    if ($accountId < 1 || $restaurantId < 1) {
        return false;
    }
    $stmt = $db->prepare('SELECT 1 FROM nomnom_merchant_restaurants WHERE account_id = ? AND restaurant_id = ?');
    $stmt->execute([$accountId, $restaurantId]);
    return (bool) $stmt->fetchColumn();
}

function ensureMerchantArea(PDO $db, int $cityId, string $areaName): int
{
    $find = $db->prepare('SELECT id FROM areas WHERE city_id = ? AND name = ?');
    $find->execute([$cityId, $areaName]);
    $existing = $find->fetchColumn();
    if ($existing !== false) {
        return (int) $existing;
    }
    $insert = $db->prepare('INSERT INTO areas(city_id, name) VALUES (?, ?)');
    $insert->execute([$cityId, $areaName]);
    return (int) $db->lastInsertId();
}

function buildUniqueRestaurantSlug(PDO $db, string $name): string
{
    $base = normalizeSearchText($name);
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $base), '-');
    $slug = $slug !== '' ? substr($slug, 0, 60) : 'quan-an';
    return 'store-' . $slug . '-' . bin2hex(random_bytes(3));
}

function validateMerchantRestaurantInput(PDO $db, array $input): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $address = trim((string) ($input['address'] ?? ''));
    $areaName = trim((string) ($input['areaName'] ?? ''));
    $cityId = (int) ($input['cityId'] ?? 0);
    $cuisineId = (int) ($input['cuisineId'] ?? 0);
    $priceLevel = (int) ($input['priceLevel'] ?? 2);
    $lat = filter_var($input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 160) {
        throw new InvalidArgumentException('Tên quán phải từ 2 đến 160 ký tự.');
    }
    if (mb_strlen($description, 'UTF-8') < 5 || mb_strlen($description, 'UTF-8') > 500) {
        throw new InvalidArgumentException('Mô tả quán phải từ 5 đến 500 ký tự.');
    }
    if (mb_strlen($address, 'UTF-8') < 5 || mb_strlen($address, 'UTF-8') > 240) {
        throw new InvalidArgumentException('Địa chỉ quán phải từ 5 đến 240 ký tự.');
    }
    if (mb_strlen($areaName, 'UTF-8') < 2 || mb_strlen($areaName, 'UTF-8') > 80) {
        throw new InvalidArgumentException('Quận / Khu vực phải từ 2 đến 80 ký tự.');
    }
    if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        throw new InvalidArgumentException('Tọa độ GPS (vĩ độ / kinh độ) không hợp lệ.');
    }
    if ($priceLevel < 1 || $priceLevel > 3) {
        throw new InvalidArgumentException('Mức giá chỉ chấp nhận từ 1 đến 3.');
    }
    $cityCheck = $db->prepare('SELECT 1 FROM cities WHERE id = ?');
    $cityCheck->execute([$cityId]);
    if (!$cityCheck->fetchColumn()) {
        throw new InvalidArgumentException('Thành phố được chọn không tồn tại.');
    }
    $cuisineCheck = $db->prepare('SELECT 1 FROM cuisines WHERE id = ?');
    $cuisineCheck->execute([$cuisineId]);
    if (!$cuisineCheck->fetchColumn()) {
        throw new InvalidArgumentException('Nhóm ẩm thực được chọn không tồn tại.');
    }

    $tagIds = [];
    if (isset($input['tagIds']) && is_array($input['tagIds'])) {
        foreach (array_slice($input['tagIds'], 0, 5) as $rawTag) {
            $tid = (int) $rawTag;
            if ($tid > 0) {
                $tagIds[$tid] = $tid;
            }
        }
    }

    return [
        'name' => $name,
        'description' => $description,
        'address' => $address,
        'cityId' => $cityId,
        'areaName' => $areaName,
        'latitude' => (float) $lat,
        'longitude' => (float) $lng,
        'priceLevel' => $priceLevel,
        'cuisineId' => $cuisineId,
        'tagIds' => array_values($tagIds),
    ];
}

function refreshSingleRestaurantSearchIndex(PDO $db, int $restaurantId): void
{
    if (isMysqlConnection($db) || $restaurantId < 1) {
        return;
    }
    $docStmt = $db->prepare('SELECT id, name, content FROM catalog_search_documents WHERE id = ?');
    $docStmt->execute([$restaurantId]);
    $doc = $docStmt->fetch();
    $db->prepare('DELETE FROM restaurant_search WHERE rowid = ?')->execute([$restaurantId]);
    if ($doc) {
        $db->prepare('INSERT INTO restaurant_search(rowid, name, content) VALUES (?, ?, ?)')->execute([
            (int) $doc['id'],
            normalizeSearchText((string) $doc['name']),
            normalizeSearchText((string) $doc['content']),
        ]);
    }
}

function createMerchantRestaurant(PDO $db, int $accountId, array $input): array
{
    $data = validateMerchantRestaurantInput($db, $input);
    $ownsTx = !$db->inTransaction();
    if ($ownsTx) {
        $db->beginTransaction();
    }
    try {
        $areaId = ensureMerchantArea($db, $data['cityId'], $data['areaName']);
        $slug = buildUniqueRestaurantSlug($db, $data['name']);
        $stmt = $db->prepare(
            "INSERT INTO restaurants(area_id, name, slug, description, address, latitude, longitude, price_level, status, is_demo, rating_sum, review_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, 5, 1)"
        );
        $stmt->execute([
            $areaId, $data['name'], $slug, $data['description'], $data['address'],
            $data['latitude'], $data['longitude'], $data['priceLevel'],
        ]);
        $restaurantId = (int) $db->lastInsertId();
        $ignore = isMysqlConnection($db) ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
        $db->prepare("{$ignore} restaurant_cuisines(restaurant_id, cuisine_id) VALUES (?, ?)")
            ->execute([$restaurantId, $data['cuisineId']]);
        if ($data['tagIds'] !== []) {
            $tagStmt = $db->prepare("{$ignore} restaurant_tags(restaurant_id, tag_id) VALUES (?, ?)");
            $validTag = $db->prepare('SELECT 1 FROM tags WHERE id = ?');
            foreach ($data['tagIds'] as $tagId) {
                $validTag->execute([$tagId]);
                if ($validTag->fetchColumn()) {
                    $tagStmt->execute([$restaurantId, $tagId]);
                }
            }
        }
        $db->prepare('INSERT INTO nomnom_merchant_restaurants(account_id, restaurant_id) VALUES (?, ?)')
            ->execute([$accountId, $restaurantId]);
        refreshSingleRestaurantSearchIndex($db, $restaurantId);
        if ($ownsTx) {
            $db->commit();
        }
        return findRestaurantDetail($db, $restaurantId) ?? ['id' => $restaurantId, 'isDemo' => false];
    } catch (Throwable $error) {
        if ($ownsTx && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function validateMerchantDishInput(PDO $db, array $input): array
{
    $restaurantId = (int) ($input['restaurantId'] ?? 0);
    $dishName = trim((string) ($input['dishName'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $cuisineId = (int) ($input['cuisineId'] ?? 0);
    $categoryId = (int) ($input['categoryId'] ?? 0);
    $priceVnd = (int) ($input['priceVnd'] ?? 0);
    $isVegetarian = !empty($input['isVegetarian']) ? 1 : 0;
    $spiceLevel = (int) ($input['spiceLevel'] ?? 0);

    if ($restaurantId < 1) {
        throw new InvalidArgumentException('Vui lòng chọn quán ăn hợp lệ.');
    }
    if (mb_strlen($dishName, 'UTF-8') < 2 || mb_strlen($dishName, 'UTF-8') > 120) {
        throw new InvalidArgumentException('Tên món ăn phải từ 2 đến 120 ký tự.');
    }
    if (mb_strlen($description, 'UTF-8') < 2 || mb_strlen($description, 'UTF-8') > 400) {
        throw new InvalidArgumentException('Mô tả món phải từ 2 đến 400 ký tự.');
    }
    if ($priceVnd < 1000 || $priceVnd > 100000000) {
        throw new InvalidArgumentException('Giá bán phải từ 1.000 đến 100.000.000 VNĐ.');
    }
    if ($spiceLevel < 0 || $spiceLevel > 3) {
        throw new InvalidArgumentException('Độ cay chỉ nhận giá trị từ 0 đến 3.');
    }
    $cCheck = $db->prepare('SELECT 1 FROM cuisines WHERE id = ?');
    $cCheck->execute([$cuisineId]);
    if (!$cCheck->fetchColumn()) {
        throw new InvalidArgumentException('Nhóm ẩm thực không hợp lệ.');
    }
    $catCheck = $db->prepare('SELECT 1 FROM categories WHERE id = ?');
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetchColumn()) {
        throw new InvalidArgumentException('Danh mục món ăn không hợp lệ.');
    }
    return compact('restaurantId', 'dishName', 'description', 'cuisineId', 'categoryId', 'priceVnd', 'isVegetarian', 'spiceLevel');
}

function addMerchantDish(PDO $db, int $accountId, array $input): array
{
    $data = validateMerchantDishInput($db, $input);
    if (!merchantOwnsRestaurant($db, $accountId, $data['restaurantId'])) {
        throw new InvalidArgumentException('Bạn không có quyền thêm món vào quán này.');
    }
    $ownsTx = !$db->inTransaction();
    if ($ownsTx) {
        $db->beginTransaction();
    }
    try {
        $findDish = $db->prepare('SELECT id FROM dishes WHERE name = ? AND cuisine_id = ?');
        $findDish->execute([$data['dishName'], $data['cuisineId']]);
        $existingDish = $findDish->fetchColumn();
        if ($existingDish !== false) {
            $dishId = (int) $existingDish;
        } else {
            $insDish = $db->prepare(
                'INSERT INTO dishes(category_id, cuisine_id, name, description, is_vegetarian, spice_level) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insDish->execute([
                $data['categoryId'], $data['cuisineId'], $data['dishName'],
                $data['description'], $data['isVegetarian'], $data['spiceLevel'],
            ]);
            $dishId = (int) $db->lastInsertId();
        }
        $checkMenu = $db->prepare('SELECT id FROM menu_items WHERE restaurant_id = ? AND dish_id = ?');
        $checkMenu->execute([$data['restaurantId'], $dishId]);
        if ($checkMenu->fetchColumn() !== false) {
            $db->prepare('UPDATE menu_items SET price_vnd = ?, is_available = 1 WHERE restaurant_id = ? AND dish_id = ?')
                ->execute([$data['priceVnd'], $data['restaurantId'], $dishId]);
        } else {
            $db->prepare('INSERT INTO menu_items(restaurant_id, dish_id, price_vnd, is_available) VALUES (?, ?, ?, 1)')
                ->execute([$data['restaurantId'], $dishId, $data['priceVnd']]);
        }
        refreshSingleRestaurantSearchIndex($db, $data['restaurantId']);
        if ($ownsTx) {
            $db->commit();
        }
        return ['restaurantId' => $data['restaurantId'], 'dishId' => $dishId, 'name' => $data['dishName'], 'priceVnd' => $data['priceVnd']];
    } catch (Throwable $error) {
        if ($ownsTx && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function removeMerchantMenuItem(PDO $db, int $accountId, int $restaurantId, int $dishId): void
{
    if (!merchantOwnsRestaurant($db, $accountId, $restaurantId)) {
        throw new InvalidArgumentException('Bạn không có quyền xóa món của quán này.');
    }
    $stmt = $db->prepare('DELETE FROM menu_items WHERE restaurant_id = ? AND dish_id = ?');
    $stmt->execute([$restaurantId, $dishId]);
    if ($stmt->rowCount() < 1) {
        throw new InvalidArgumentException('Không tìm thấy món cần xóa trong thực đơn.');
    }
    refreshSingleRestaurantSearchIndex($db, $restaurantId);
}

function listMerchantRestaurants(PDO $db, int $accountId): array
{
    $stmt = $db->prepare(
        'SELECT restaurant_id FROM nomnom_merchant_restaurants WHERE account_id = ? ORDER BY created_at DESC, restaurant_id DESC'
    );
    $stmt->execute([$accountId]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $items = [];
    foreach ($ids as $id) {
        $detail = findRestaurantDetail($db, $id);
        if ($detail !== null) {
            $items[] = $detail;
        }
    }
    return $items;
}

function listMerchantBookings(PDO $db, int $accountId): array
{
    $stmt = $db->prepare(
        'SELECT b.id, b.restaurant_id, r.name AS restaurant_name, b.guest_name, b.email,
                b.booking_date, b.guest_count, b.status, b.coupon_code, b.note, b.created_at
         FROM nomnom_bookings b
         JOIN nomnom_merchant_restaurants mr ON mr.restaurant_id = b.restaurant_id
         JOIN restaurants r ON r.id = b.restaurant_id
         WHERE mr.account_id = ?
         ORDER BY b.booking_date DESC, b.id DESC
         LIMIT 50'
    );
    $stmt->execute([$accountId]);
    return array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'restaurantId' => (int) $row['restaurant_id'],
        'restaurantName' => (string) $row['restaurant_name'],
        'guestName' => (string) $row['guest_name'],
        'email' => (string) $row['email'],
        'bookingDate' => (string) $row['booking_date'],
        'guestCount' => (int) $row['guest_count'],
        'status' => (string) $row['status'],
        'couponCode' => $row['coupon_code'] ? (string) $row['coupon_code'] : null,
        'note' => (string) $row['note'],
    ], $stmt->fetchAll());
}

function updateMerchantBookingStatus(PDO $db, int $accountId, int $bookingId, string $status): void
{
    if (!in_array($status, ['confirmed', 'cancelled'], true) || $bookingId < 1) {
        throw new InvalidArgumentException('Trạng thái đặt bàn không hợp lệ.');
    }
    $check = $db->prepare(
        'SELECT b.id FROM nomnom_bookings b
         JOIN nomnom_merchant_restaurants mr ON mr.restaurant_id = b.restaurant_id
         WHERE b.id = ? AND mr.account_id = ?'
    );
    $check->execute([$bookingId, $accountId]);
    if (!$check->fetchColumn()) {
        throw new InvalidArgumentException('Bạn không có quyền cập nhật lịch đặt bàn này.');
    }
    $db->prepare('UPDATE nomnom_bookings SET status = ? WHERE id = ?')->execute([$status, $bookingId]);
}
