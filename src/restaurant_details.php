<?php
declare(strict_types=1);

function restaurantSummaryColumns(): string
{
    return 'r.id, r.name, r.description, r.address, r.latitude, r.longitude, r.price_level,
        r.is_demo, r.review_count, CASE WHEN r.review_count = 0 THEN 0.0
        ELSE ROUND(1.0 * r.rating_sum / r.review_count, 1) END AS rating, '
        . restaurantPriceSql() . ' AS min_price_vnd';
}

function findRestaurantDetail(PDO $db, int $id): ?array
{
    $statement = $db->prepare('SELECT ' . restaurantSummaryColumns() . " FROM restaurants r WHERE r.id = :id AND r.status = 'active'");
    $statement->execute([':id' => $id]);
    $row = $statement->fetch();
    if (!$row) return null;
    $place = mapRestaurantRow($row);
    $place['cuisine'] = implode(', ', restaurantDetailNames($db, $id, 'cuisines', 'restaurant_cuisines', 'cuisine_id'));
    $place['tags'] = restaurantDetailNames($db, $id, 'tags', 'restaurant_tags', 'tag_id');
    $place['amenities'] = restaurantDetailNames($db, $id, 'amenities', 'restaurant_amenities', 'amenity_id');
    $place['menu'] = restaurantMenu($db, $id);
    $hours = $db->prepare('SELECT weekday, opens_minute, closes_minute FROM opening_hours WHERE restaurant_id = :id ORDER BY weekday');
    $hours->execute([':id' => $id]);
    $place['hours'] = array_map(static fn(array $hour): array => ['weekday' => (int) $hour['weekday'],
        'opensMinute' => (int) $hour['opens_minute'], 'closesMinute' => (int) $hour['closes_minute']], $hours->fetchAll());
    return $place;
}

/** Identifiers are internal constants, never request parameters. */
function restaurantDetailNames(PDO $db, int $id, string $table, string $join, string $foreignKey): array
{
    $statement = $db->prepare("SELECT n.name FROM {$table} n JOIN {$join} j ON j.{$foreignKey} = n.id WHERE j.restaurant_id = :id ORDER BY n.name");
    $statement->execute([':id' => $id]);
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

function restaurantMenu(PDO $db, int $id): array
{
    $statement = $db->prepare('SELECT d.id, d.name, d.description, d.is_vegetarian, d.spice_level, mi.price_vnd
        FROM menu_items mi JOIN dishes d ON d.id = mi.dish_id
        WHERE mi.restaurant_id = :id AND mi.is_available = 1 ORDER BY mi.price_vnd, d.id');
    $statement->execute([':id' => $id]);
    return array_map(static fn(array $row): array => ['id' => (string) $row['id'], 'name' => $row['name'],
        'description' => $row['description'], 'priceVnd' => (int) $row['price_vnd'],
        'isVegetarian' => (bool) $row['is_vegetarian'], 'spiceLevel' => (int) $row['spice_level']], $statement->fetchAll());
}
