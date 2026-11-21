<?php
declare(strict_types=1);

function validateDiscoveryOptions(array $input): array
{
    foreach ($input as $key => $value) {
        if (!in_array($key, ['q', 'limit', 'offset', 'vegetarian', 'maxPrice', 'maxSpice', 'sort'], true) || !is_string($value)) {
            throw new InvalidArgumentException('Tham số khám phá không hợp lệ hoặc bị lặp.');
        }
    }
    $query = trim($input['q'] ?? '');
    $length = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
    if ($length > 80 || preg_match('/[\x00-\x1F]/', $query) === 1) {
        throw new InvalidArgumentException('Từ khóa tối đa 80 ký tự và không chứa ký tự điều khiển.');
    }
    $limit = parseDiscoveryInteger($input['limit'] ?? '12', 'Số món');
    $offset = parseDiscoveryInteger($input['offset'] ?? '0', 'Vị trí bắt đầu');
    $maxPrice = parseDiscoveryInteger($input['maxPrice'] ?? '0', 'Ngân sách');
    $maxSpice = parseDiscoveryInteger($input['maxSpice'] ?? '3', 'Độ cay');
    if ($limit < 1 || $limit > 50 || $offset > 1000000 || $maxPrice > 100000000 || $maxSpice > 3) {
        throw new InvalidArgumentException('Bộ lọc hoặc phân trang nằm ngoài giới hạn.');
    }
    $vegetarian = null;
    if (array_key_exists('vegetarian', $input)) {
        if (!in_array($input['vegetarian'], ['0', '1'], true)) throw new InvalidArgumentException('Bộ lọc món chay không hợp lệ.');
        $vegetarian = $input['vegetarian'] === '1';
    }
    $sort = $input['sort'] ?? 'rating';
    if (!in_array($sort, ['rating', 'price', 'random'], true)) throw new InvalidArgumentException('Cách sắp xếp không hợp lệ.');
    if ($sort === 'random' && ($limit !== 1 || $offset !== 0)) {
        throw new InvalidArgumentException('Chọn ngẫu nhiên cần limit=1 và offset=0.');
    }
    return compact('query', 'limit', 'offset', 'vegetarian', 'maxPrice', 'maxSpice', 'sort');
}

function parseDiscoveryInteger(mixed $value, string $label): int
{
    if (!is_string($value) || preg_match('/^(?:0|[1-9]\d*)$/', $value) !== 1) {
        throw new InvalidArgumentException($label . ' phải là số nguyên không âm.');
    }
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false) throw new InvalidArgumentException($label . ' không hợp lệ.');
    return (int) $number;
}

function discoveryConditions(PDO $db, array $options): array
{
    $where = ["r.status='active'", 'mi.is_available=1', 'd.spice_level<=:maxSpice'];
    $params = [':maxSpice' => $options['maxSpice']];
    if ($options['maxPrice'] > 0) { $where[] = 'mi.price_vnd<=:maxPrice'; $params[':maxPrice'] = $options['maxPrice']; }
    if ($options['vegetarian'] !== null) {
        $where[] = 'd.is_vegetarian=:vegetarian'; $params[':vegetarian'] = $options['vegetarian'] ? 1 : 0;
    }
    $mysql = isMysqlConnection($db);
    if (!$mysql) $db->sqliteCreateFunction('nomnom_normalize', 'normalizeSearchText', 1, PDO::SQLITE_DETERMINISTIC);
    $text = $mysql ? "CONCAT(d.name, ' ', d.description, ' ', r.name, ' ', c.name)"
        : "nomnom_normalize(d.name || ' ' || d.description || ' ' || r.name || ' ' || c.name)";
    $tokens = discoveryTokens($options['query']);
    if ($options['query'] !== '' && !$tokens) $where[] = '1=0';
    foreach ($tokens as $index => $token) {
        $parameter = ':term' . $index;
        $where[] = "{$text} LIKE {$parameter}";
        $params[$parameter] = '%' . $token . '%';
    }
    return [implode(' AND ', $where), $params];
}

function discoverDishes(PDO $db, array $options): array
{
    [$where, $params] = discoveryConditions($db, $options);
    $joins = 'menu_items mi JOIN dishes d ON d.id=mi.dish_id JOIN restaurants r ON r.id=mi.restaurant_id JOIN cuisines c ON c.id=d.cuisine_id';
    $count = $db->prepare("SELECT COUNT(*) FROM {$joins} WHERE {$where}");
    bindDiscoveryParams($count, $params); $count->execute();
    $total = (int) $count->fetchColumn();
    $order = $options['sort'] === 'price' ? 'mi.price_vnd, rating DESC' : 'rating DESC, mi.price_vnd';
    if ($options['sort'] === 'random') $order = isMysqlConnection($db) ? 'RAND()' : 'RANDOM()';
    $statement = $db->prepare("SELECT d.id AS dish_id, d.name AS dish_name, d.description AS dish_description,
        d.is_vegetarian, d.spice_level, mi.price_vnd, r.id AS restaurant_id, r.name AS restaurant_name,
        r.address, r.latitude, r.longitude, r.is_demo, c.name AS cuisine,
        CASE WHEN r.review_count=0 THEN 0.0 ELSE ROUND(1.0*r.rating_sum/r.review_count,1) END AS rating
        FROM {$joins} WHERE {$where} ORDER BY {$order}, d.id, r.id LIMIT :limit OFFSET :offset");
    bindDiscoveryParams($statement, $params);
    $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
    $statement->execute();
    return ['data' => array_map('mapDiscoveryRow', $statement->fetchAll()), 'meta' => discoveryMeta($options, $total)];
}

function discoveryTokens(string $query): array
{
    $normalized = normalizeSearchText($query);
    return $normalized === '' || !preg_match_all('/[\p{L}\p{N}]+/u', $normalized, $matches) ? [] : $matches[0];
}

/** A group votes on dishes independently of the restaurants serving them. */
function listUniqueGroupDishes(PDO $db, array $options): array
{
    [$where, $params] = discoveryConditions($db, $options);
    $joins = 'menu_items mi JOIN dishes d ON d.id=mi.dish_id JOIN restaurants r ON r.id=mi.restaurant_id JOIN cuisines c ON c.id=d.cuisine_id';
    $count = $db->prepare("SELECT COUNT(DISTINCT d.id) FROM {$joins} WHERE {$where}");
    bindDiscoveryParams($count, $params); $count->execute();
    $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT d.id, d.name, d.description, c.name AS cuisine, MIN(mi.price_vnd) AS price
        FROM {$joins} WHERE {$where} GROUP BY d.id, d.name, d.description, c.name
        ORDER BY d.id LIMIT :limit OFFSET :offset");
    bindDiscoveryParams($statement, $params);
    $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
    $statement->execute();
    $items = array_map(static fn(array $row): array => [
        'id' => (string) $row['id'], 'name' => (string) $row['name'],
        'description' => (string) $row['description'], 'cuisine' => (string) $row['cuisine'],
        'priceVnd' => (int) $row['price'],
    ], $statement->fetchAll());
    return ['data' => $items, 'meta' => discoveryMeta($options, $total)];
}

function bindDiscoveryParams(PDOStatement $statement, array $params): void
{
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function mapDiscoveryRow(array $row): array
{
    return [
        'id' => (string) $row['dish_id'], 'name' => (string) $row['dish_name'],
        'description' => (string) $row['dish_description'], 'cuisine' => (string) ($row['cuisine'] ?? ''),
        'priceVnd' => (int) $row['price_vnd'], 'isVegetarian' => (bool) $row['is_vegetarian'],
        'spiceLevel' => (int) $row['spice_level'], 'isDemo' => (bool) $row['is_demo'],
        'restaurant' => ['id' => (string) $row['restaurant_id'], 'name' => (string) $row['restaurant_name'],
            'address' => (string) $row['address'], 'lat' => (float) $row['latitude'],
            'lng' => (float) $row['longitude'], 'rating' => (float) $row['rating']],
    ];
}

function discoveryMeta(array $options, int $total): array
{
    return ['limit' => $options['limit'], 'offset' => $options['offset'], 'total' => $total,
        'hasMore' => $options['offset'] + $options['limit'] < $total];
}
