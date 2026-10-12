<?php
declare(strict_types=1);

function parseRestaurantFilters(array $input): array
{
    $maxPrice = parsePositiveInteger($input['maxPrice'] ?? '0', 'Ngân sách');
    $priceLevel = parsePositiveInteger($input['priceLevel'] ?? '0', 'Mức giá');
    $rating = $input['minRating'] ?? '0';
    $vegetarian = $input['vegetarian'] ?? '0';
    $sort = $input['sort'] ?? 'relevance';
    if ($maxPrice > 100000000 || $priceLevel > 4 || !is_string($rating)
        || preg_match('/^[0-5](?:\.\d)?$/', $rating) !== 1 || (float) $rating > 5
        || !in_array($vegetarian, ['0', '1'], true)
        || !in_array($sort, ['relevance', 'rating', 'price_asc', 'reviews', 'random'], true)) {
        throw new InvalidArgumentException('Bộ lọc giá, đánh giá hoặc sắp xếp không hợp lệ.');
    }
    return ['maxPrice' => $maxPrice, 'priceLevel' => $priceLevel, 'minRating' => (float) $rating,
        'vegetarian' => $vegetarian === '1', 'sort' => $sort];
}

function restaurantFilterSql(array $options, array &$params): string
{
    $clauses = [];
    if (($options['priceLevel'] ?? 0) > 0) {
        $clauses[] = 'r.price_level = :priceLevel';
        $params[':priceLevel'] = $options['priceLevel'];
    }
    if (($options['minRating'] ?? 0) > 0) {
        $clauses[] = 'r.review_count > 0 AND 1.0 * r.rating_sum / r.review_count >= CAST(:minRating AS DECIMAL(3,1))';
        $params[':minRating'] = $options['minRating'];
    }
    $menu = ['fm.restaurant_id = r.id', 'fm.is_available = 1'];
    if (($options['maxPrice'] ?? 0) > 0) {
        $menu[] = 'fm.price_vnd <= :maxPrice';
        $params[':maxPrice'] = $options['maxPrice'];
    }
    if ($options['vegetarian'] ?? false) $menu[] = 'fd.is_vegetarian = 1';
    if (count($menu) > 2) {
        $clauses[] = 'EXISTS (SELECT 1 FROM menu_items fm JOIN dishes fd ON fd.id = fm.dish_id WHERE ' . implode(' AND ', $menu) . ')';
    }
    return $clauses ? ' AND ' . implode(' AND ', $clauses) : '';
}

function restaurantPriceSql(): string
{
    return '(SELECT MIN(pm.price_vnd) FROM menu_items pm WHERE pm.restaurant_id = r.id AND pm.is_available = 1)';
}

function restaurantOrderSql(PDO $db, array $options, string $relevance = ''): string
{
    return match ($options['sort'] ?? 'relevance') {
        'price_asc' => 'CASE WHEN min_price_vnd IS NULL THEN 1 ELSE 0 END, min_price_vnd, r.id',
        'reviews' => 'r.review_count DESC, rating DESC, r.id',
        'random' => isMysqlConnection($db) ? 'RAND()' : 'RANDOM()',
        'rating' => 'rating DESC, r.id',
        default => $relevance . 'rating DESC, r.id',
    };
}
