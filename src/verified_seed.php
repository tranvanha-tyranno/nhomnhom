<?php
declare(strict_types=1);

function verifiedRestaurantBlueprints(): array
{
    return [
        // TP. Hồ Chí Minh — Quận 7 (gần ĐH Tôn Đức Thắng / Nguyễn Thị Thập / Phú Mỹ Hưng)
        [
            'slug' => 'verified-pho-phu-vuong-q7', 'cityId' => 1, 'area' => 'Quận 7',
            'name' => 'Phở Phú Vương — Nguyễn Thị Thập',
            'description' => 'Phở bò nước dùng đậm đà, thịt bò tươi mềm, phục vụ nhanh gần khu vực Đại học Tôn Đức Thắng.',
            'address' => '339 Nguyễn Thị Thập, Phường Tân Phong, Quận 7, TP Hồ Chí Minh',
            'lat' => 10.7405, 'lng' => 106.7068, 'priceLevel' => 2, 'ratingSum' => 576, 'reviewCount' => 120,
            'cuisines' => [2, 4], 'tags' => [1, 2, 9, 12],
            'menu' => [[2, 65000], [22, 85000], [18, 60000]],
        ],
        [
            'slug' => 'verified-com-tam-phuc-loc-tho-q7', 'cityId' => 1, 'area' => 'Quận 7',
            'name' => 'Cơm Tấm Phúc Lộc Thọ — Quận 7',
            'description' => 'Cơm tấm sườn nướng than hoa thơm lừng, bì chả trứng ốp la và nước mắm kẹo chuẩn vị Sài Gòn.',
            'address' => '416 Nguyễn Thị Thập, Phường Tân Quy, Quận 7, TP Hồ Chí Minh',
            'lat' => 10.7412, 'lng' => 106.7034, 'priceLevel' => 2, 'ratingSum' => 528, 'reviewCount' => 112,
            'cuisines' => [4], 'tags' => [2, 3, 5, 10],
            'menu' => [[7, 55000], [27, 75000], [5, 35000]],
        ],
        [
            'slug' => 'verified-bun-bo-hue-song-huong-q7', 'cityId' => 1, 'area' => 'Quận 7',
            'name' => 'Bún Bò Huế Sông Hương — Lê Văn Lương',
            'description' => 'Nước lèo thơm sả ruốc Huế, giò heo mềm và chả cua đậm vị, quán rộng rãi gần ĐH Tôn Đức Thắng.',
            'address' => '68 Lê Văn Lương, Phường Tân Hưng, Quận 7, TP Hồ Chí Minh',
            'lat' => 10.7358, 'lng' => 106.7016, 'priceLevel' => 1, 'ratingSum' => 442, 'reviewCount' => 94,
            'cuisines' => [1], 'tags' => [1, 2, 6, 9],
            'menu' => [[1, 48000], [21, 65000], [5, 30000]],
        ],
        [
            'slug' => 'verified-chay-sen-vang-q7', 'cityId' => 1, 'area' => 'Quận 7',
            'name' => 'Ẩm Thực Chay Sen Vàng — Quận 7',
            'description' => 'Không gian thanh tịnh, thực đơn cơm chay, lẩu nấm và gỏi rau củ thanh đạm chế biến mỗi ngày.',
            'address' => '215 Nguyễn Hữu Thọ, Phường Tân Hưng, Quận 7, TP Hồ Chí Minh',
            'lat' => 10.7319, 'lng' => 106.7052, 'priceLevel' => 2, 'ratingSum' => 456, 'reviewCount' => 95,
            'cuisines' => [3], 'tags' => [2, 3, 4, 11],
            'menu' => [[3, 45000], [23, 65000], [11, 40000], [19, 45000], [20, 145000]],
        ],
        [
            'slug' => 'verified-bun-dau-q7', 'cityId' => 1, 'area' => 'Quận 7',
            'name' => 'Bún Đậu Mắm Tôm Làng Vòng — Quận 7',
            'description' => 'Mẹt bún đậu đầy đặn đậu hũ chiên giòn, chả cốm, thịt chân giò luộc, rất hợp tụ tập nhóm bạn.',
            'address' => '182 Lâm Văn Bền, Phường Tân Kiểng, Quận 7, TP Hồ Chí Minh',
            'lat' => 10.7468, 'lng' => 106.7124, 'priceLevel' => 1, 'ratingSum' => 414, 'reviewCount' => 90,
            'cuisines' => [2], 'tags' => [2, 3, 5, 6],
            'menu' => [[17, 55000], [6, 52000], [14, 48000]],
        ],

        // TP. Hồ Chí Minh — Quận 1, Quận 3, Quận 5, Phú Nhuận, Thủ Đức
        [
            'slug' => 'verified-pho-hoa-pasteur', 'cityId' => 1, 'area' => 'Quận 3',
            'name' => 'Phở Hòa Pasteur',
            'description' => 'Thương hiệu phở lâu đời tại Sài Gòn với nước dùng ngọt thanh, tô đầy thịt tái nạm gầu gân.',
            'address' => '260C Pasteur, Phường Võ Thị Sáu, Quận 3, TP Hồ Chí Minh',
            'lat' => 10.7852, 'lng' => 106.6889, 'priceLevel' => 2, 'ratingSum' => 864, 'reviewCount' => 180,
            'cuisines' => [2, 4], 'tags' => [1, 2, 9, 12],
            'menu' => [[2, 75000], [22, 95000], [5, 35000]],
        ],
        [
            'slug' => 'verified-banh-mi-huynh-hoa', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Bánh Mì Huynh Hoa — Lê Thị Riêng',
            'description' => 'Ổ bánh mì giòn rụm nổi tiếng Sài Gòn với lớp pate béo ngậy và nhân thịt nguội chả lụa đầy ắp.',
            'address' => '26 Lê Thị Riêng, Phường Bến Thành, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7717, 'lng' => 106.6926, 'priceLevel' => 2, 'ratingSum' => 960, 'reviewCount' => 200,
            'cuisines' => [4], 'tags' => [7, 8, 10, 12],
            'menu' => [[4, 58000], [24, 68000], [5, 35000]],
        ],
        [
            'slug' => 'verified-pho-le-nguyen-trai', 'cityId' => 1, 'area' => 'Quận 5',
            'name' => 'Phở Lệ — Nguyễn Trãi',
            'description' => 'Phở bò phong cách miền Nam béo thơm đặc trưng, bò viên giòn sật và rau thơm tươi ăn kèm.',
            'address' => '415 Nguyễn Trãi, Phường 7, Quận 5, TP Hồ Chí Minh',
            'lat' => 10.7554, 'lng' => 106.6716, 'priceLevel' => 2, 'ratingSum' => 768, 'reviewCount' => 160,
            'cuisines' => [4], 'tags' => [1, 2, 9, 12],
            'menu' => [[2, 70000], [22, 90000], [13, 68000]],
        ],
        [
            'slug' => 'verified-com-tam-ba-ghien', 'cityId' => 1, 'area' => 'Phú Nhuận',
            'name' => 'Cơm Tấm Ba Ghiền',
            'description' => 'Quán cơm tấm trứ danh với miếng sườn cốt lết khổng lồ nướng mật ong vàng ươm, đạt chuẩn Michelin Bib Gourmand.',
            'address' => '84 Đặng Văn Ngữ, Phường 10, Phú Nhuận, TP Hồ Chí Minh',
            'lat' => 10.7931, 'lng' => 106.6710, 'priceLevel' => 2, 'ratingSum' => 931, 'reviewCount' => 190,
            'cuisines' => [4], 'tags' => [2, 3, 10, 12],
            'menu' => [[7, 68000], [27, 92000], [15, 65000]],
        ],
        [
            'slug' => 'verified-bun-bo-hue-dong-ba', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Bún Bò Huế Đông Ba',
            'description' => 'Hương vị bún bò cố đô đậm đà giữa trung tâm Quận 1 với chả cua, nạm bò và giò heo chuẩn vị.',
            'address' => '207B Nguyễn Văn Thủ, Phường Đa Kao, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7885, 'lng' => 106.6986, 'priceLevel' => 2, 'ratingSum' => 658, 'reviewCount' => 140,
            'cuisines' => [1], 'tags' => [1, 2, 9, 12],
            'menu' => [[1, 62000], [21, 82000], [16, 58000]],
        ],
        [
            'slug' => 'verified-banh-xeo-46a', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Bánh Xèo 46A Đinh Công Tráng',
            'description' => 'Bánh xèo miền Nam vỏ vàng giòn rụm, nhân tôm thịt giá đậu xanh ăn kèm rổ rau rừng tươi ngon.',
            'address' => '46A Đinh Công Tráng, Phường Tân Định, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7897, 'lng' => 106.6908, 'priceLevel' => 2, 'ratingSum' => 705, 'reviewCount' => 150,
            'cuisines' => [4], 'tags' => [3, 4, 5, 12],
            'menu' => [[9, 95000], [29, 130000], [5, 45000]],
        ],
        [
            'slug' => 'verified-bun-cha-145-bui-vien', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Bún Chả 145 Bùi Viện',
            'description' => 'Bún chả Hà Nội nướng than thơm phức, nước chấm chua ngọt hài hòa cùng nem cua bể giòn tan.',
            'address' => '145 Bùi Viện, Phường Phạm Ngũ Lão, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7674, 'lng' => 106.6939, 'priceLevel' => 1, 'ratingSum' => 564, 'reviewCount' => 120,
            'cuisines' => [2], 'tags' => [2, 3, 6, 10],
            'menu' => [[6, 55000], [26, 72000], [5, 35000]],
        ],
        [
            'slug' => 'verified-banh-cuon-tay-ho', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Bánh Cuốn Tây Hồ — Đinh Tiên Hoàng',
            'description' => 'Bánh cuốn tráng tay mỏng mịn nhân thịt mộc nhĩ, ăn cùng chả lụa, bánh tôm và nước mắm pha thanh.',
            'address' => '127 Đinh Tiên Hoàng, Phường Đa Kao, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7915, 'lng' => 106.6976, 'priceLevel' => 1, 'ratingSum' => 528, 'reviewCount' => 110,
            'cuisines' => [2], 'tags' => [1, 6, 7, 12],
            'menu' => [[12, 48000], [5, 35000], [18, 52000]],
        ],
        [
            'slug' => 'verified-hu-tieu-nam-vang-nhan-quan', 'cityId' => 1, 'area' => 'Quận 1',
            'name' => 'Hủ Tiếu Nam Vang Nhân Quán',
            'description' => 'Hủ tiếu khô và nước đậm đà tôm tươi, thịt băm, trứng cút và sốt trộn đặc trưng.',
            'address' => 'A68 Nguyễn Trãi, Phường Nguyễn Cư Trinh, Quận 1, TP Hồ Chí Minh',
            'lat' => 10.7697, 'lng' => 106.6915, 'priceLevel' => 2, 'ratingSum' => 621, 'reviewCount' => 135,
            'cuisines' => [4], 'tags' => [1, 2, 3, 9],
            'menu' => [[13, 68000], [16, 65000], [5, 35000]],
        ],
        [
            'slug' => 'verified-hum-vegetarian-thao-dien', 'cityId' => 1, 'area' => 'TP Thủ Đức',
            'name' => 'Nhà Hàng Chay Hum Vegetarian',
            'description' => 'Nhà hàng chay cao cấp không gian xanh mát, sáng tạo từ nguyên liệu hữu cơ và nấm tươi Việt Nam.',
            'address' => '32 Đường D10, Thảo Điền, TP Thủ Đức, TP Hồ Chí Minh',
            'lat' => 10.8035, 'lng' => 106.7332, 'priceLevel' => 3, 'ratingSum' => 784, 'reviewCount' => 160,
            'cuisines' => [3], 'tags' => [2, 3, 4, 11],
            'menu' => [[3, 95000], [23, 135000], [19, 85000], [20, 260000]],
        ],

        // Hà Nội
        [
            'slug' => 'verified-pho-thin-lo-duc', 'cityId' => 2, 'area' => 'Hai Bà Trưng',
            'name' => 'Phở Thìn 13 Lò Đúc',
            'description' => 'Món phở tái lăn xào tỏi trứ danh đất Hà Thành với nước dùng béo thơm và hành lá xanh đầy bát.',
            'address' => '13 Lò Đúc, Quận Hai Bà Trưng, Hà Nội',
            'lat' => 21.0169, 'lng' => 105.8555, 'priceLevel' => 2, 'ratingSum' => 864, 'reviewCount' => 180,
            'cuisines' => [2], 'tags' => [1, 2, 9, 12],
            'menu' => [[2, 75000], [22, 95000], [18, 65000]],
        ],
        [
            'slug' => 'verified-bun-cha-huong-lien', 'cityId' => 2, 'area' => 'Hai Bà Trưng',
            'name' => 'Bún Chả Hương Liên (Bún Chả Obama)',
            'description' => 'Quán bún chả gia truyền nổi tiếng với chả nướng thơm lừng, nem hải sản giòn rụm và nước chấm đậm đà.',
            'address' => '24 Lê Văn Hưu, Quận Hai Bà Trưng, Hà Nội',
            'lat' => 21.0181, 'lng' => 105.8527, 'priceLevel' => 1, 'ratingSum' => 912, 'reviewCount' => 190,
            'cuisines' => [2], 'tags' => [2, 3, 5, 12],
            'menu' => [[6, 55000], [26, 75000], [17, 60000]],
        ],
        [
            'slug' => 'verified-banh-cuon-ba-hoanh', 'cityId' => 2, 'area' => 'Hai Bà Trưng',
            'name' => 'Bánh Cuốn Bà Hoành — Tô Hiến Thành',
            'description' => 'Bánh cuốn Thanh Trì truyền thống tráng nguội mịn dẻo, ăn kèm chả quế, thịt nướng và tinh dầu cà cuống.',
            'address' => '66 Tô Hiến Thành, Quận Hai Bà Trưng, Hà Nội',
            'lat' => 21.0138, 'lng' => 105.8498, 'priceLevel' => 1, 'ratingSum' => 552, 'reviewCount' => 115,
            'cuisines' => [2], 'tags' => [1, 6, 7, 12],
            'menu' => [[12, 45000], [10, 48000], [18, 50000]],
        ],
        [
            'slug' => 'verified-com-chay-nang-tam', 'cityId' => 2, 'area' => 'Hoàn Kiếm',
            'name' => 'Cơm Chay Nàng Tấm',
            'description' => 'Nhà hàng chay lâu đời tại Hoàn Kiếm, phục vụ cơm chay truyền thống, cháo nấm và lẩu nấm thanh vị.',
            'address' => '79A Trần Hưng Đạo, Quận Hoàn Kiếm, Hà Nội',
            'lat' => 21.0226, 'lng' => 105.8442, 'priceLevel' => 2, 'ratingSum' => 576, 'reviewCount' => 120,
            'cuisines' => [3], 'tags' => [2, 3, 4, 11],
            'menu' => [[3, 65000], [23, 90000], [11, 55000], [20, 195000]],
        ],

        // Đà Nẵng
        [
            'slug' => 'verified-mi-quang-ba-mua', 'cityId' => 3, 'area' => 'Hải Châu',
            'name' => 'Mì Quảng Bà Mua — Trần Bình Trọng',
            'description' => 'Đặc sản mì Quảng chuẩn vị với sợi mì mềm dẻo, nước nhân tôm thịt gà ta đậm đà và bánh tráng nướng giòn.',
            'address' => '19 Trần Bình Trọng, Quận Hải Châu, Đà Nẵng',
            'lat' => 16.0686, 'lng' => 108.2188, 'priceLevel' => 1, 'ratingSum' => 672, 'reviewCount' => 140,
            'cuisines' => [1], 'tags' => [1, 2, 6, 12],
            'menu' => [[8, 45000], [28, 60000], [5, 30000]],
        ],
        [
            'slug' => 'verified-banh-xeo-ba-duong', 'cityId' => 3, 'area' => 'Hải Châu',
            'name' => 'Bánh Xèo Bà Dưỡng — Hoàng Diệu',
            'description' => 'Bánh xèo giòn rụm kèm nem lụi nướng thơm, nổi tiếng với nước chấm tương đậu phộng gan béo bùi độc quyền.',
            'address' => 'K280/23 Hoàng Diệu, Quận Hải Châu, Đà Nẵng',
            'lat' => 16.0581, 'lng' => 108.2151, 'priceLevel' => 1, 'ratingSum' => 768, 'reviewCount' => 160,
            'cuisines' => [1], 'tags' => [2, 3, 5, 12],
            'menu' => [[9, 45000], [29, 65000], [14, 42000]],
        ],
        [
            'slug' => 'verified-bun-cha-ca-ba-lu', 'cityId' => 3, 'area' => 'Thanh Khê',
            'name' => 'Bún Chả Cá Bà Lữ — Hùng Vương',
            'description' => 'Bún chả cá Đà Nẵng nước dùng ngọt thanh từ xương cá và bí đỏ, ăn kèm mắm ruốc ớt xanh.',
            'address' => '319 Hùng Vương, Quận Thanh Khê, Đà Nẵng',
            'lat' => 16.0672, 'lng' => 108.2102, 'priceLevel' => 1, 'ratingSum' => 564, 'reviewCount' => 120,
            'cuisines' => [1], 'tags' => [1, 2, 6, 9],
            'menu' => [[10, 40000], [16, 42000], [8, 45000]],
        ],

        // Huế & Đà Lạt
        [
            'slug' => 'verified-bun-bo-hue-ba-tuyet', 'cityId' => 4, 'area' => 'Trung tâm Huế',
            'name' => 'Bún Bò Huế Bà Tuyết',
            'description' => 'Bún bò Huế gia truyền đậm hương mắm ruốc, gân bò hầm mềm, chả cua béo thơm nổi tiếng đất Cố đô.',
            'address' => '47 Nguyễn Công Trứ, Phường Phú Hội, TP Huế',
            'lat' => 16.4694, 'lng' => 107.5964, 'priceLevel' => 1, 'ratingSum' => 624, 'reviewCount' => 130,
            'cuisines' => [1], 'tags' => [1, 2, 9, 12],
            'menu' => [[1, 45000], [21, 60000], [16, 40000]],
        ],
        [
            'slug' => 'verified-banh-mi-xiu-mai-hoang-dieu', 'cityId' => 6, 'area' => 'Trung tâm Đà Lạt',
            'name' => 'Bánh Mì Xíu Mại Hoàng Diệu',
            'description' => 'Bữa sáng ấm nóng đặc trưng xứ sương mù với chén xíu mại da heo béo mềm chấm bánh mì giòn tan.',
            'address' => '37 Hoàng Diệu, Phường 5, TP Đà Lạt',
            'lat' => 11.9437, 'lng' => 108.4310, 'priceLevel' => 1, 'ratingSum' => 648, 'reviewCount' => 135,
            'cuisines' => [1, 4], 'tags' => [1, 6, 7, 12],
            'menu' => [[4, 30000], [24, 42000], [11, 35000]],
        ],
    ];
}

function ensureVerifiedArea(PDO $db, int $cityId, string $areaName): int
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

function upsertVerifiedRestaurant(PDO $db, array $item, int $areaId): int
{
    $find = $db->prepare('SELECT id FROM restaurants WHERE slug = ?');
    $find->execute([$item['slug']]);
    $existing = $find->fetchColumn();
    if ($existing !== false) {
        $id = (int) $existing;
        $update = $db->prepare(
            "UPDATE restaurants SET area_id=?, name=?, description=?, address=?, latitude=?, longitude=?,
             price_level=?, status='active', is_demo=0, rating_sum=?, review_count=? WHERE id=?"
        );
        $update->execute([
            $areaId, $item['name'], $item['description'], $item['address'],
            $item['lat'], $item['lng'], $item['priceLevel'], $item['ratingSum'], $item['reviewCount'], $id,
        ]);
        return $id;
    }
    $insert = $db->prepare(
        "INSERT INTO restaurants(area_id, name, slug, description, address, latitude, longitude, price_level, status, is_demo, rating_sum, review_count)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, ?, ?)"
    );
    $insert->execute([
        $areaId, $item['name'], $item['slug'], $item['description'], $item['address'],
        $item['lat'], $item['lng'], $item['priceLevel'], $item['ratingSum'], $item['reviewCount'],
    ]);
    return (int) $db->lastInsertId();
}

function syncVerifiedRestaurantLinks(PDO $db, int $restaurantId, array $item): int
{
    $ignore = isMysqlConnection($db) ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
    $cuisineStmt = $db->prepare("{$ignore} restaurant_cuisines(restaurant_id, cuisine_id) VALUES (?, ?)");
    foreach ($item['cuisines'] as $cuisineId) {
        $cuisineStmt->execute([$restaurantId, (int) $cuisineId]);
    }
    $tagStmt = $db->prepare("{$ignore} restaurant_tags(restaurant_id, tag_id) VALUES (?, ?)");
    foreach ($item['tags'] as $tagId) {
        $tagStmt->execute([$restaurantId, (int) $tagId]);
    }
    $menuCount = 0;
    foreach ($item['menu'] as [$dishId, $priceVnd]) {
        $check = $db->prepare('SELECT id FROM menu_items WHERE restaurant_id = ? AND dish_id = ?');
        $check->execute([$restaurantId, (int) $dishId]);
        if ($check->fetchColumn() !== false) {
            $db->prepare('UPDATE menu_items SET price_vnd = ?, is_available = 1 WHERE restaurant_id = ? AND dish_id = ?')
                ->execute([(int) $priceVnd, $restaurantId, (int) $dishId]);
        } else {
            $db->prepare('INSERT INTO menu_items(restaurant_id, dish_id, price_vnd, is_available) VALUES (?, ?, ?, 1)')
                ->execute([$restaurantId, (int) $dishId, (int) $priceVnd]);
        }
        $menuCount++;
    }
    return $menuCount;
}

function refreshVerifiedSearchIndex(PDO $db, array $restaurantIds): void
{
    if (isMysqlConnection($db) || empty($restaurantIds)) {
        return;
    }
    $del = $db->prepare('DELETE FROM restaurant_search WHERE rowid = ?');
    $ins = $db->prepare(
        'INSERT INTO restaurant_search(rowid, name, content)
         SELECT id, name, content FROM catalog_search_documents WHERE id = ?'
    );
    foreach ($restaurantIds as $id) {
        $del->execute([$id]);
        $ins->execute([$id]);
    }
}

function seedVerifiedRestaurants(PDO $db): array
{
    $blueprints = verifiedRestaurantBlueprints();
    $ids = [];
    $menuItems = 0;
    $db->beginTransaction();
    try {
        foreach ($blueprints as $item) {
            $areaId = ensureVerifiedArea($db, (int) $item['cityId'], (string) $item['area']);
            $restaurantId = upsertVerifiedRestaurant($db, $item, $areaId);
            $menuItems += syncVerifiedRestaurantLinks($db, $restaurantId, $item);
            $ids[] = $restaurantId;
        }
        refreshVerifiedSearchIndex($db, $ids);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
    return ['restaurants' => count($ids), 'menuItems' => $menuItems];
}
