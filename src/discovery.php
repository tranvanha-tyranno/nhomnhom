<?php
declare(strict_types=1);

function validateDiscoveryOptions(array $input): array
{
    foreach ($input as $key => $value) {
        if (!in_array($key, ['q', 'limit', 'offset', 'vegetarian'], true) || is_array($value)) {
            throw new InvalidArgumentException('Tham số khám phá không hợp lệ hoặc bị lặp.');
        }
    }

    $query = trim((string) ($input['q'] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
    if ($length > 80 || preg_match('/[\x00-\x1F]/', $query) === 1) {
        throw new InvalidArgumentException('Từ khóa tối đa 80 ký tự và không chứa ký tự điều khiển.');
    }

    $limit = parseDiscoveryInteger($input['limit'] ?? '12', 'Số món');
    $offset = parseDiscoveryInteger($input['offset'] ?? '0', 'Vị trí bắt đầu');
    if ($limit < 1 || $limit > 50) {
        throw new InvalidArgumentException('Số món phải từ 1 đến 50.');
    }
    if ($offset < 0 || $offset > 1000000) {
        throw new InvalidArgumentException('Vị trí bắt đầu không hợp lệ.');
    }

    $vegetarian = null;
    if (array_key_exists('vegetarian', $input)) {
        if (!in_array((string) $input['vegetarian'], ['0', '1'], true)) {
            throw new InvalidArgumentException('Bộ lọc món chay không hợp lệ.');
        }
        $vegetarian = $input['vegetarian'] === '1';
    }

    return compact('query', 'limit', 'offset', 'vegetarian');
}

function parseDiscoveryInteger(mixed $value, string $label): int
{
    if (!is_string($value) || preg_match('/^(?:0|[1-9]\d*)$/', $value) !== 1) {
        throw new InvalidArgumentException($label . ' phải là số nguyên không âm.');
    }
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false) {
        throw new InvalidArgumentException($label . ' không hợp lệ.');
    }
    return (int) $number;
}

function discoverDishes(PDO $db, array $options): array
{
    if (isMysqlConnection($db)) {
        return discoverDishesMysql($db, $options);
    }
    $tokens = discoveryTokens($options['query']);
    $match = implode(' AND ', array_map(
        static fn(string $token): string => '"' . str_replace('"', '""', $token) . '"*',
        $tokens
    ));
    $joins = 'menu_items mi JOIN dishes d ON d.id = mi.dish_id
        JOIN restaurants r ON r.id = mi.restaurant_id';
    $where = ["r.status = 'active'", 'mi.is_available = 1'];
    $params = [];
    if ($match !== '') {
        $joins .= ' JOIN restaurant_search ON restaurant_search.rowid = r.id';
        $where[] = 'restaurant_search MATCH :match';
        $params[':match'] = $match;
    }
    if ($options['vegetarian'] !== null) {
        $where[] = 'd.is_vegetarian = :vegetarian';
        $params[':vegetarian'] = $options['vegetarian'] ? 1 : 0;
    }
    $whereSql = implode(' AND ', $where);

    try {
        $count = $db->prepare("SELECT COUNT(*) FROM {$joins} WHERE {$whereSql}");
        bindDiscoveryParams($count, $params);
        $count->execute();
        $total = (int) $count->fetchColumn();

        $rank = $match === '' ? '' : 'bm25(restaurant_search), ';
        $sql = "SELECT d.id AS dish_id, d.name AS dish_name, d.description AS dish_description,
                    d.is_vegetarian, d.spice_level, mi.price_vnd,
                    r.id AS restaurant_id, r.name AS restaurant_name, r.address,
                    r.latitude, r.longitude, r.review_count, r.is_demo,
                    CASE WHEN r.review_count = 0 THEN 0.0
                         ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
                    (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc
                     JOIN cuisines c ON c.id = rc.cuisine_id
                     WHERE rc.restaurant_id = r.id) AS cuisine
                FROM {$joins}
                WHERE {$whereSql}
                ORDER BY {$rank} rating DESC, d.id, r.id
                LIMIT :limit OFFSET :offset";
        $statement = $db->prepare($sql);
        bindDiscoveryParams($statement, $params);
        $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
        $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
        $statement->execute();
        $rows = array_map('mapDiscoveryRow', $statement->fetchAll());
    } catch (PDOException $error) {
        if ($match === '' || !str_contains(strtolower($error->getMessage()), 'no such table')) {
            throw $error;
        }
        return discoverDishesWithLike($db, $options, $tokens);
    }

    return ['data' => $rows, 'meta' => discoveryMeta($options, $total)];
}

function discoverDishesMysql(PDO $db, array $options): array
{
    $tokens = discoveryTokens($options['query']);
    $where = ["r.status='active'", 'mi.is_available=1'];
    $params = [];
    foreach ($tokens as $index => $token) {
        $dishName = ':dishName' . $index; $dishDescription = ':dishDescription' . $index; $restaurantName = ':restaurantName' . $index; $cuisine = ':dishCuisine' . $index;
        $where[] = "(d.name LIKE {$dishName} OR d.description LIKE {$dishDescription} OR r.name LIKE {$restaurantName} OR EXISTS (SELECT 1 FROM cuisines cu WHERE cu.id=d.cuisine_id AND cu.name LIKE {$cuisine}))";
        $params[$dishName] = '%' . $token . '%'; $params[$dishDescription] = '%' . $token . '%'; $params[$restaurantName] = '%' . $token . '%'; $params[$cuisine] = '%' . $token . '%';
    }
    if ($options['vegetarian'] !== null) { $where[] = 'd.is_vegetarian=:vegetarian'; $params[':vegetarian'] = $options['vegetarian'] ? 1 : 0; }
    $whereSql = implode(' AND ', $where);
    $joins = 'menu_items mi JOIN dishes d ON d.id=mi.dish_id JOIN restaurants r ON r.id=mi.restaurant_id';
    $count = $db->prepare("SELECT COUNT(*) FROM {$joins} WHERE {$whereSql}"); bindDiscoveryParams($count, $params); $count->execute(); $total = (int) $count->fetchColumn();
    $statement = $db->prepare("SELECT d.id AS dish_id, d.name AS dish_name, d.description AS dish_description, d.is_vegetarian, d.spice_level, mi.price_vnd,
        r.id AS restaurant_id, r.name AS restaurant_name, r.address, r.latitude, r.longitude, r.review_count, r.is_demo,
        CASE WHEN r.review_count=0 THEN 0.0 ELSE ROUND(1.0*r.rating_sum/r.review_count,1) END AS rating,
        (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM restaurant_cuisines rc JOIN cuisines c ON c.id=rc.cuisine_id WHERE rc.restaurant_id=r.id) AS cuisine
        FROM {$joins} WHERE {$whereSql} ORDER BY rating DESC, d.id, r.id LIMIT :limit OFFSET :offset");
    bindDiscoveryParams($statement, $params); $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT); $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT); $statement->execute();
    return ['data' => array_map('mapDiscoveryRow', $statement->fetchAll()), 'meta' => discoveryMeta($options, $total)];
}

function discoverDishesWithLike(PDO $db, array $options, array $tokens): array
{
    $where = ["r.status = 'active'", 'mi.is_available = 1'];
    $params = [];
    foreach ($tokens as $index => $token) {
        $placeholder = ':token' . $index;
        $where[] = "lower(d.name || ' ' || d.description) LIKE {$placeholder}";
        $params[$placeholder] = '%' . strtolower($token) . '%';
    }
    if ($options['vegetarian'] !== null) {
        $where[] = 'd.is_vegetarian = :vegetarian';
        $params[':vegetarian'] = $options['vegetarian'] ? 1 : 0;
    }
    $whereSql = implode(' AND ', $where);
    $joins = 'menu_items mi JOIN dishes d ON d.id = mi.dish_id JOIN restaurants r ON r.id = mi.restaurant_id';
    $count = $db->prepare("SELECT COUNT(*) FROM {$joins} WHERE {$whereSql}");
    bindDiscoveryParams($count, $params);
    $count->execute();
    $total = (int) $count->fetchColumn();
    $sql = "SELECT d.id AS dish_id, d.name AS dish_name, d.description AS dish_description,
                d.is_vegetarian, d.spice_level, mi.price_vnd,
                r.id AS restaurant_id, r.name AS restaurant_name, r.address,
                r.latitude, r.longitude, r.review_count, r.is_demo,
                CASE WHEN r.review_count = 0 THEN 0.0
                     ELSE round(1.0 * r.rating_sum / r.review_count, 1) END AS rating,
                (SELECT group_concat(c.name, ', ') FROM restaurant_cuisines rc
                 JOIN cuisines c ON c.id = rc.cuisine_id
                 WHERE rc.restaurant_id = r.id) AS cuisine
            FROM {$joins} WHERE {$whereSql}
            ORDER BY rating DESC, d.id, r.id LIMIT :limit OFFSET :offset";
    $statement = $db->prepare($sql);
    bindDiscoveryParams($statement, $params);
    $statement->bindValue(':limit', $options['limit'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $options['offset'], PDO::PARAM_INT);
    $statement->execute();
    return ['data' => array_map('mapDiscoveryRow', $statement->fetchAll()), 'meta' => discoveryMeta($options, $total)];
}

function discoveryTokens(string $query): array
{
    $normalized = normalizeSearchText($query);
    return $normalized === '' || !preg_match_all('/[\p{L}\p{N}]+/u', $normalized, $matches)
        ? []
        : $matches[0];
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
        'id' => (string) $row['dish_id'],
        'name' => (string) $row['dish_name'],
        'description' => (string) $row['dish_description'],
        'cuisine' => (string) ($row['cuisine'] ?? ''),
        'priceVnd' => (int) $row['price_vnd'],
        'isVegetarian' => (bool) $row['is_vegetarian'],
        'spiceLevel' => (int) $row['spice_level'],
        'isDemo' => (bool) $row['is_demo'],
        'restaurant' => [
            'id' => (string) $row['restaurant_id'],
            'name' => (string) $row['restaurant_name'],
            'address' => (string) $row['address'],
            'lat' => (float) $row['latitude'],
            'lng' => (float) $row['longitude'],
            'rating' => (float) $row['rating'],
        ],
    ];
}

function discoveryMeta(array $options, int $total): array
{
    return [
        'limit' => $options['limit'],
        'offset' => $options['offset'],
        'total' => $total,
        'hasMore' => $options['offset'] + $options['limit'] < $total,
    ];
}
