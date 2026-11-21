<?php
declare(strict_types=1);

function findRestaurantDetail(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $mysql = isMysqlConnection($db);
    $cuisineSql = $mysql
        ? "(SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id) AS cuisine"
        : "(SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id) AS cuisine";
    $tagsSql = $mysql
        ? "(SELECT GROUP_CONCAT(t.name SEPARATOR '||') FROM restaurant_tags rt JOIN tags t ON t.id=rt.tag_id WHERE rt.restaurant_id=r.id) AS tags_text"
        : "(SELECT json_group_array(t.name) FROM restaurant_tags rt JOIN tags t ON t.id=rt.tag_id WHERE rt.restaurant_id=r.id) AS tags";

    $stmt = $db->prepare("SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
            r.price_level, r.is_demo, r.review_count,
            CASE WHEN r.review_count = 0 THEN 0.0 ELSE ROUND(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
            {$cuisineSql}, {$tagsSql}
        FROM restaurants r WHERE r.id = :id AND r.status = 'active'");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $restaurant = mapRestaurantRow($row);
    $menuStmt = $db->prepare("SELECT d.id AS dish_id, d.name, d.description, d.is_vegetarian, d.spice_level,
            mi.price_vnd, c.name AS cuisine, COALESCE(e.rating, 1000) AS elo_rating
        FROM menu_items mi
        JOIN dishes d ON d.id = mi.dish_id
        JOIN cuisines c ON c.id = d.cuisine_id
        LEFT JOIN nomnom_dish_elo e ON e.dish_id = d.id
        WHERE mi.restaurant_id = :id AND mi.is_available = 1
        ORDER BY mi.price_vnd ASC, d.name ASC");
    $menuStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $menuStmt->execute();

    $menu = array_map(static fn(array $item): array => [
        'dishId' => (int) $item['dish_id'],
        'name' => (string) $item['name'],
        'description' => (string) $item['description'],
        'cuisine' => (string) $item['cuisine'],
        'priceVnd' => (int) $item['price_vnd'],
        'isVegetarian' => (bool) $item['is_vegetarian'],
        'spiceLevel' => (int) $item['spice_level'],
        'eloRating' => (int) $item['elo_rating'],
    ], $menuStmt->fetchAll());

    $prices = array_column($menu, 'priceVnd');
    $restaurant['menu'] = $menu;
    $restaurant['menuCount'] = count($menu);
    $restaurant['minPriceVnd'] = $prices ? min($prices) : 0;
    $restaurant['maxPriceVnd'] = $prices ? max($prices) : 0;
    return $restaurant;
}
