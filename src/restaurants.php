<?php
declare(strict_types=1);

require_once __DIR__ . '/search.php';

function validateSearchOptions(
    string $query,
    int $page,
    int $pageSize,
    int $priceLevel = 0,
    string $sort = 'relevance',
    bool $verifiedOnly = false
): array {
    $length = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
    if ($length > 80 || preg_match('/[\x00-\x1F]/', $query) === 1) {
        throw new InvalidArgumentException('Từ khóa tối đa 80 ký tự và không chứa ký tự điều khiển.');
    }
    if ($page < 1 || $page > 10000 || $pageSize < 1 || $pageSize > 100) {
        throw new InvalidArgumentException('Tham số phân trang không hợp lệ.');
    }
    if ($priceLevel < 0 || $priceLevel > 3) {
        throw new InvalidArgumentException('Mức giá chỉ chấp nhận từ 0 đến 3.');
    }
    if (!in_array($sort, ['relevance', 'rating', 'popular', 'price_asc'], true)) {
        throw new InvalidArgumentException('Cách sắp xếp không hợp lệ.');
    }
    return [
        'query' => trim($query),
        'page' => $page,
        'pageSize' => $pageSize,
        'priceLevel' => $priceLevel,
        'sort' => $sort,
        'verifiedOnly' => $verifiedOnly,
    ];
}

function parseSearchOptions(array $input): array
{
    $allowed = ['q', 'page', 'pageSize', 'priceLevel', 'sort', 'verifiedOnly'];
    foreach ($input as $key => $value) {
        if (!in_array($key, $allowed, true) || is_array($value)) {
            throw new InvalidArgumentException('Tham số tìm kiếm không hợp lệ hoặc bị lặp.');
        }
    }
    $page = parsePositiveInteger($input['page'] ?? '1', 'Trang');
    $pageSize = parsePositiveInteger($input['pageSize'] ?? '12', 'Số kết quả mỗi trang');
    $priceLevel = parsePositiveInteger($input['priceLevel'] ?? '0', 'Mức giá');
    $sort = (string) ($input['sort'] ?? 'relevance');
    $verifiedRaw = (string) ($input['verifiedOnly'] ?? '0');
    if (!in_array($verifiedRaw, ['0', '1'], true)) {
        throw new InvalidArgumentException('Bộ lọc xác minh không hợp lệ.');
    }
    return validateSearchOptions((string) ($input['q'] ?? ''), $page, $pageSize, $priceLevel, $sort, $verifiedRaw === '1');
}

function parsePositiveInteger(mixed $value, string $label): int
{
    if (!is_string($value) || preg_match('/^(?:0|[1-9]\d*)$/', $value) !== 1) {
        throw new InvalidArgumentException($label . ' phải là số nguyên dương.');
    }
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false) {
        throw new InvalidArgumentException($label . ' không hợp lệ.');
    }
    return (int) $number;
}

function restaurantFilterClauses(array $options): array
{
    $clauses = [];
    $params = [];
    $priceLevel = (int) ($options['priceLevel'] ?? 0);
    if ($priceLevel > 0) {
        $clauses[] = 'r.price_level = :priceLevel';
        $params[':priceLevel'] = $priceLevel;
    }
    if (!empty($options['verifiedOnly'])) {
        $clauses[] = 'r.is_demo = 0';
    }
    return [$clauses, $params];
}

function restaurantOrderSql(array $options, bool $hasMatch): string
{
    $sort = (string) ($options['sort'] ?? 'relevance');
    if ($sort === 'popular') {
        return 'r.review_count DESC, rating DESC, r.id';
    }
    if ($sort === 'price_asc') {
        return 'r.price_level ASC, rating DESC, r.id';
    }
    if ($sort === 'rating') {
        return 'rating DESC, r.review_count DESC, r.id';
    }
    $prefix = $hasMatch ? 'bm25(restaurant_search, 3.0, 1.0), ' : '';
    return $prefix . 'rating DESC, r.id';
}

function findRestaurants(PDO $db, array $options): array
{
    if (isMysqlConnection($db)) {
        return findRestaurantsMysql($db, $options);
    }
    $query = normalizeSearchText($options['query']);
    $tokens = $query === '' ? [] : (preg_match_all('/[\p{L}\p{N}]+/u', $query, $matches) ? $matches[0] : []);
    if ($options['query'] !== '' && count($tokens) === 0) {
        return emptyRestaurantResults($options);
    }

    $match = implode(' AND ', array_map(static fn(string $token): string => '"' . str_replace('"', '""', $token) . '"*', $tokens));
    $from = $match !== '' ? 'restaurants r JOIN restaurant_search ON restaurant_search.rowid = r.id' : 'restaurants r';
    $whereParts = ["r.status = 'active'"];
    $params = [];
    if ($match !== '') {
        $whereParts[] = 'restaurant_search MATCH :match';
        $params[':match'] = $match;
    }
    [$extraClauses, $extraParams] = restaurantFilterClauses($options);
    $where = implode(' AND ', array_merge($whereParts, $extraClauses));
    $params = array_merge($params, $extraParams);

    try {
        $totalStatement = $db->prepare("SELECT COUNT(*) FROM {$from} WHERE {$where}");
        bindRestaurantParams($totalStatement, $params);
        $totalStatement->execute();
        $total = (int) $totalStatement->fetchColumn();
        $orderSql = restaurantOrderSql($options, $match !== '');
        $sql = "SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
                    r.price_level, r.is_demo, r.review_count,
                    CASE WHEN r.review_count = 0 THEN 0.0 ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
                    (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id = rc.cuisine_id WHERE rc.restaurant_id = r.id) AS cuisine,
                    (SELECT json_group_array(t.name) FROM restaurant_tags rt JOIN tags t ON t.id = rt.tag_id WHERE rt.restaurant_id = r.id) AS tags
                FROM {$from} WHERE {$where} ORDER BY {$orderSql} LIMIT :limit OFFSET :offset";
        $statement = $db->prepare($sql);
        bindRestaurantParams($statement, $params);
        $statement->bindValue(':limit', $options['pageSize'], PDO::PARAM_INT);
        $statement->bindValue(':offset', ($options['page'] - 1) * $options['pageSize'], PDO::PARAM_INT);
        $statement->execute();
        $rows = array_map('mapRestaurantRow', $statement->fetchAll());
    } catch (PDOException $error) {
        if ($match === '' || !str_contains(strtolower($error->getMessage()), 'no such table')) {
            throw $error;
        }
        return findRestaurantsWithLike($db, $options, $tokens);
    }

    return restaurantResults($rows, $options, $total);
}

function findRestaurantsMysql(PDO $db, array $options): array
{
    $tokens = discoveryTokens($options['query']);
    $where = ["r.status = 'active'"];
    $params = [];
    foreach ($tokens as $index => $token) {
        $parts = [];
        foreach (['name', 'description', 'address'] as $field) {
            $placeholder = ':r' . $field . $index;
            $parts[] = "r.{$field} LIKE {$placeholder}";
            $params[$placeholder] = '%' . $token . '%';
        }
        $cuisinePlaceholder = ':cuisine' . $index;
        $tagPlaceholder = ':tag' . $index;
        $dishNamePlaceholder = ':dishName' . $index;
        $dishDescriptionPlaceholder = ':dishDescription' . $index;
        $parts[] = "EXISTS (SELECT 1 FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id AND c.name LIKE {$cuisinePlaceholder})";
        $parts[] = "EXISTS (SELECT 1 FROM restaurant_tags rt JOIN tags t ON t.id=rt.tag_id WHERE rt.restaurant_id=r.id AND t.name LIKE {$tagPlaceholder})";
        $parts[] = "EXISTS (SELECT 1 FROM menu_items mi JOIN dishes d ON d.id=mi.dish_id WHERE mi.restaurant_id=r.id AND mi.is_available=1 AND (d.name LIKE {$dishNamePlaceholder} OR d.description LIKE {$dishDescriptionPlaceholder}))";
        $params[$cuisinePlaceholder] = '%' . $token . '%';
        $params[$tagPlaceholder] = '%' . $token . '%';
        $params[$dishNamePlaceholder] = '%' . $token . '%';
        $params[$dishDescriptionPlaceholder] = '%' . $token . '%';
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    [$extraClauses, $extraParams] = restaurantFilterClauses($options);
    $whereSql = implode(' AND ', array_merge($where, $extraClauses));
    $params = array_merge($params, $extraParams);
    $orderSql = restaurantOrderSql($options, false);
    $count = $db->prepare("SELECT COUNT(*) FROM restaurants r WHERE {$whereSql}");
    bindRestaurantParams($count, $params);
    $count->execute();
    $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
        r.price_level, r.is_demo, r.review_count,
        CASE WHEN r.review_count=0 THEN 0.0 ELSE ROUND(1.0*r.rating_sum/r.review_count, 1) END AS rating,
        (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id) AS cuisine,
        (SELECT GROUP_CONCAT(t.name SEPARATOR '||') FROM restaurant_tags rt JOIN tags t ON t.id=rt.tag_id WHERE rt.restaurant_id=r.id) AS tags_text
        FROM restaurants r WHERE {$whereSql} ORDER BY {$orderSql} LIMIT :limit OFFSET :offset");
    bindRestaurantParams($statement, $params);
    $statement->bindValue(':limit', $options['pageSize'], PDO::PARAM_INT);
    $statement->bindValue(':offset', ($options['page'] - 1) * $options['pageSize'], PDO::PARAM_INT);
    $statement->execute();
    return restaurantResults(array_map('mapRestaurantRow', $statement->fetchAll()), $options, $total);
}

function bindRestaurantParams(PDOStatement $statement, array $params): void
{
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function findRestaurantsWithLike(PDO $db, array $options, array $tokens): array
{
    $conditions = ["r.status = 'active'"];
    $params = [];
    foreach ($tokens as $index => $token) {
        $placeholder = ':token' . $index;
        $conditions[] = "lower(r.name || ' ' || r.description || ' ' || r.address) LIKE {$placeholder}";
        $params[$placeholder] = '%' . strtolower($token) . '%';
    }
    [$extraClauses, $extraParams] = restaurantFilterClauses($options);
    $where = implode(' AND ', array_merge($conditions, $extraClauses));
    $params = array_merge($params, $extraParams);
    $orderSql = restaurantOrderSql($options, false);
    $count = $db->prepare("SELECT COUNT(*) FROM restaurants r WHERE {$where}");
    bindRestaurantParams($count, $params);
    $count->execute();
    $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
        r.price_level, r.is_demo, r.review_count,
        CASE WHEN r.review_count = 0 THEN 0.0 ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
        (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id = rc.cuisine_id WHERE rc.restaurant_id = r.id) AS cuisine,
        (SELECT json_group_array(t.name) FROM restaurant_tags rt JOIN tags t ON t.id = rt.tag_id WHERE rt.restaurant_id = r.id) AS tags
        FROM restaurants r WHERE {$where} ORDER BY {$orderSql} LIMIT :limit OFFSET :offset");
    bindRestaurantParams($statement, $params);
    $statement->bindValue(':limit', $options['pageSize'], PDO::PARAM_INT);
    $statement->bindValue(':offset', ($options['page'] - 1) * $options['pageSize'], PDO::PARAM_INT);
    $statement->execute();
    return restaurantResults(array_map('mapRestaurantRow', $statement->fetchAll()), $options, $total);
}

function mapRestaurantRow(array $row): array
{
    $tags = isset($row['tags_text'])
        ? (($row['tags_text'] ?? '') === '' ? [] : explode('||', (string) $row['tags_text']))
        : json_decode((string) ($row['tags'] ?? '[]'), true);
    return [
        'id' => (string) $row['id'],
        'name' => (string) $row['name'],
        'cuisine' => (string) ($row['cuisine'] ?? ''),
        'description' => (string) $row['description'],
        'address' => (string) $row['address'],
        'lat' => (float) $row['latitude'],
        'lng' => (float) $row['longitude'],
        'tags' => is_array($tags) ? array_values(array_map('strval', $tags)) : [],
        'priceLevel' => (int) $row['price_level'],
        'rating' => (float) $row['rating'],
        'isDemo' => (bool) $row['is_demo'],
        'reviewCount' => (int) $row['review_count'],
    ];
}

function emptyRestaurantResults(array $options): array
{
    return restaurantResults([], $options, 0);
}

function restaurantResults(array $data, array $options, int $total): array
{
    return [
        'data' => $data,
        'pagination' => [
            'page' => $options['page'],
            'pageSize' => $options['pageSize'],
            'total' => $total,
            'totalPages' => $total === 0 ? 0 : (int) ceil($total / $options['pageSize']),
        ],
    ];
}
