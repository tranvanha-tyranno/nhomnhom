<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

function checkExplore(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function rejectsExplore(callable $action): void
{
    try { $action(); } catch (InvalidArgumentException) { return; }
    throw new RuntimeException('Expected input rejection.');
}

$csrf = nomnomCsrfToken();
requireNomNomCsrf(['csrfToken' => $csrf]);
set_error_handler(static function (int $severity, string $message): never { throw new RuntimeException($message); });
rejectsExplore(fn() => requireNomNomCsrf(['csrfToken' => ['invalid']]));
restore_error_handler();
rejectsExplore(fn() => requireNomNomCsrf(['csrfToken' => 'incorrect']));

$options = parseSearchOptions(['maxPrice' => '50000', 'vegetarian' => '1', 'minRating' => '4', 'sort' => 'price_asc']);
checkExplore($options['maxPrice'] === 50000 && $options['vegetarian'], 'Parse combined filters');
foreach ([['maxPrice' => '-1'], ['maxPrice' => '1e5'], ['minRating' => '5.1'], ['vegetarian' => 'yes'],
    ['sort' => 'r.id; DROP TABLE restaurants'], ['priceLevel' => '5'], ['sort' => []], ['id' => '1']] as $invalid) {
    rejectsExplore(fn() => parseSearchOptions($invalid));
}

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(file_get_contents(__DIR__ . '/../../database/migrations/001_catalog.sql'));
$db->exec("INSERT INTO countries VALUES(1,'VN','Vietnam'); INSERT INTO cities VALUES(1,1,'HCM',10,106);
    INSERT INTO areas VALUES(1,1,'Quan 1'); INSERT INTO cuisines VALUES(1,'Viet',''); INSERT INTO categories VALUES(1,'Mon');
    INSERT INTO dishes VALUES(1,1,1,'Rau','',1,0),(2,1,1,'Thit','',0,2);
    INSERT INTO restaurants(id,area_id,name,slug,description,address,latitude,longitude,price_level,rating_sum,review_count,status)
    VALUES(1,1,'Quan A','a','','A',10,106,1,9,2,'active'),(2,1,'Quan B','b','','B',10,106,2,5,1,'active'),
    (3,1,'Quan C','c','','C',10,106,3,3,1,'active'),(4,1,'Quan D','d','','D',10,106,1,5,1,'closed'),
    (5,1,'Quan E','e','','E',10,106,4,0,0,'active');
    INSERT INTO menu_items VALUES(1,1,1,40000,1),(2,1,2,20000,1),(3,2,1,80000,1),(4,2,2,30000,1),
    (5,3,1,10000,0),(6,4,1,5000,1);
    INSERT INTO restaurant_cuisines VALUES(1,1); INSERT INTO opening_hours VALUES(1,1,540,1260);
    INSERT INTO amenities VALUES(1,'Wifi'); INSERT INTO restaurant_amenities VALUES(1,1);");

$result = findRestaurants($db, $options);
checkExplore(array_column($result['data'], 'id') === ['1'], 'Budget and vegetarian must match the SAME available dish');
checkExplore($result['pagination']['total'] === 1, 'Filter count matches records');
checkExplore($result['data'][0]['minPriceVnd'] === 20000, 'Expose available menu starting price');
$sorted = findRestaurants($db, parseSearchOptions(['sort' => 'price_asc']));
checkExplore(array_column($sorted['data'], 'id') === ['1', '2', '3', '5'], 'Price order puts missing prices last with stable ties');
checkExplore(findRestaurants($db, parseSearchOptions(['priceLevel' => '2']))['pagination']['total'] === 1, 'Price level filter');
checkExplore(findRestaurants($db, parseSearchOptions(['minRating' => '4.6']))['data'][0]['id'] === '2', 'Minimum rating filter');
checkExplore(findRestaurants($db, parseSearchOptions(['q' => '!!!']))['pagination']['total'] === 0, 'Punctuation query is empty');
checkExplore(findRestaurants($db, parseSearchOptions(['q' => 'Quan', 'maxPrice' => '50000', 'vegetarian' => '1']))['pagination']['total'] === 1, 'Fallback search keeps filters');
$random = findRestaurants($db, parseSearchOptions(['sort' => 'random', 'vegetarian' => '1', 'maxPrice' => '50000', 'pageSize' => '1']));
checkExplore($random['data'][0]['id'] === '1', 'Random selection respects all filters');

$detail = findRestaurantDetail($db, 1);
checkExplore($detail['name'] === 'Quan A' && count($detail['menu']) === 2, 'Restaurant detail with menu');
checkExplore($detail['menu'][0]['priceVnd'] === 20000 && !$detail['menu'][0]['isVegetarian'], 'Menu mapped and sorted');
checkExplore($detail['amenities'] === ['Wifi'] && $detail['hours'][0]['opensMinute'] === 540, 'Amenities and hours');
checkExplore(findRestaurantDetail($db, 3)['menu'] === [], 'Unavailable menu excluded');
checkExplore(findRestaurantDetail($db, 4) === null && findRestaurantDetail($db, 999) === null, 'Closed or missing restaurant hidden');
rejectsExplore(fn() => validateSavedRestaurant(['restaurantId' => '1', 'action' => 'save']));
rejectsExplore(fn() => validateSavedRestaurant(['restaurantId' => 1, 'action' => 'bad']));
ensureSavedRestaurantTable($db);
$save = validateSavedRestaurant(['restaurantId' => 1, 'action' => 'save']);
changeSavedRestaurant($db, 'session-a', $save);
changeSavedRestaurant($db, 'session-a', $save);
checkExplore(count(listSavedRestaurants($db, 'session-a')) === 1, 'Saving twice is idempotent');
checkExplore(listSavedRestaurants($db, 'session-b') === [], 'Saved restaurants isolated by session');
changeSavedRestaurant($db, 'session-b', ['restaurantId' => 1, 'action' => 'remove']);
checkExplore(count(listSavedRestaurants($db, 'session-a')) === 1, 'Cannot remove another session saved restaurant');
rejectsExplore(fn() => changeSavedRestaurant($db, 'session-a', ['restaurantId' => 4, 'action' => 'save']));
changeSavedRestaurant($db, 'session-a', ['restaurantId' => 1, 'action' => 'remove']);
checkExplore(listSavedRestaurants($db, 'session-a') === [], 'Remove saved restaurant');
fwrite(STDOUT, "PHP exploration tests passed\n");
