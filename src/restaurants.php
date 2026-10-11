<?php
declare(strict_types=1);

require_once __DIR__ . '/search.php';

function validateSearchOptions(string $query, int $page, int $pageSize): array
{
    $length = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
    if ($length > 80 || preg_match('/[\x00-\x1F]/', $query) === 1) {
        throw new InvalidArgumentException('Từ khóa tối đa 80 ký tự và không chứa ký tự điều khiển.');
    }
    if ($page < 1 || $page > 10000 || $pageSize < 1 || $pageSize > 100) {
        throw new InvalidArgumentException('Tham số phân trang không hợp lệ.');
    }
    return ['query' => trim($query), 'page' => $page, 'pageSize' => $pageSize];
}

function parseSearchOptions(array $input): array
{
    foreach ($input as $key => $value) {
        if (!in_array($key, ['q', 'page', 'pageSize'], true) || is_array($value)) {
            throw new InvalidArgumentException('Tham số tìm kiếm không hợp lệ hoặc bị lặp.');
        }
    }
    $page = parsePositiveInteger($input['page'] ?? '1', 'Trang');
    $pageSize = parsePositiveInteger($input['pageSize'] ?? '12', 'Số kết quả mỗi trang');
    return validateSearchOptions((string) ($input['q'] ?? ''), $page, $pageSize);
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
    $where = $match !== '' ? "r.status = 'active' AND restaurant_search MATCH :match" : "r.status = 'active'";
    $params = $match !== '' ? [':match' => $match] : [];

    try {
        $totalStatement = $db->prepare("SELECT COUNT(*) FROM {$from} WHERE {$where}");
        $totalStatement->execute($params);
        $total = (int) $totalStatement->fetchColumn();
        $order = $match !== '' ? 'bm25(restaurant_search, 3.0, 1.0), ' : '';
        $sql = "SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
                    r.price_level, r.is_demo, r.review_count,
                    CASE WHEN r.review_count = 0 THEN 0.0 ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
                    (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id = rc.cuisine_id WHERE rc.restaurant_id = r.id) AS cuisine,
                    (SELECT json_group_array(t.name) FROM restaurant_tags rt JOIN tags t ON t.id = rt.tag_id WHERE rt.restaurant_id = r.id) AS tags
                FROM {$from} WHERE {$where} ORDER BY {$order}rating DESC, r.id LIMIT :limit OFFSET :offset";
        $statement = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_STR);
        }
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
    $whereSql = implode(' AND ', $where);
    $count = $db->prepare("SELECT COUNT(*) FROM restaurants r WHERE {$whereSql}");
    bindRestaurantParams($count, $params);
    $count->execute();
    $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
        r.price_level, r.is_demo, r.review_count,
        CASE WHEN r.review_count=0 THEN 0.0 ELSE ROUND(1.0*r.rating_sum/r.review_count, 1) END AS rating,
        (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id) AS cuisine,
        (SELECT GROUP_CONCAT(t.name SEPARATOR '||') FROM restaurant_tags rt JOIN tags t ON t.id=rt.tag_id WHERE rt.restaurant_id=r.id) AS tags_text
        FROM restaurants r WHERE {$whereSql} ORDER BY rating DESC, r.id LIMIT :limit OFFSET :offset");
    bindRestaurantParams($statement, $params);
    $statement->bindValue(':limit', $options['pageSize'], PDO::PARAM_INT);
    $statement->bindValue(':offset', ($options['page'] - 1) * $options['pageSize'], PDO::PARAM_INT);
    $statement->execute();
    return restaurantResults(array_map('mapRestaurantRow', $statement->fetchAll()), $options, $total);
}

function bindRestaurantParams(PDOStatement $statement, array $params): void
{
    foreach ($params as $key => $value) $statement->bindValue($key, $value, PDO::PARAM_STR);
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
    $where = implode(' AND ', $conditions);
    $count = $db->prepare("SELECT COUNT(*) FROM restaurants r WHERE {$where}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT r.id, r.name, r.description, r.address, r.latitude, r.longitude,
        r.price_level, r.is_demo, r.review_count,
        CASE WHEN r.review_count = 0 THEN 0.0 ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
        (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id = rc.cuisine_id WHERE rc.restaurant_id = r.id) AS cuisine,
        (SELECT json_group_array(t.name) FROM restaurant_tags rt JOIN tags t ON t.id = rt.tag_id WHERE rt.restaurant_id = r.id) AS tags
        FROM restaurants r WHERE {$where} ORDER BY rating DESC, r.id LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
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
