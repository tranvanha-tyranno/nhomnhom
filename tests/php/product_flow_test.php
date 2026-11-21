<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

function checkFlow(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function rejectsFlow(callable $operation, string $message): void
{
    try { $operation(); } catch (InvalidArgumentException) { return; }
    throw new RuntimeException($message);
}

nomnomSessionKey();
$_SESSION['account_id'] = 42;
$identity = nomnomIdentityKey();
session_regenerate_id(true);
checkFlow($identity === nomnomIdentityKey(), 'Account data must survive session renewal');
$_SESSION['account_id'] = 43;
checkFlow($identity !== nomnomIdentityKey(), 'Accounts must have isolated storage keys');
unset($_SESSION['account_id']);

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(file_get_contents(__DIR__ . '/../../database/migrations/001_catalog.sql'));
ensureNomNomFeatureTables($db);
$db->exec("INSERT INTO countries VALUES (1,'VN','Vietnam');
    INSERT INTO cities VALUES (1,1,'HCM',10,106);
    INSERT INTO areas VALUES (1,1,'Q1');
    INSERT INTO cuisines VALUES (1,'Viet','');
    INSERT INTO categories VALUES (1,'Main');
    INSERT INTO dishes VALUES (1,1,1,'Phở bò','Nóng',0,0),(2,1,1,'Bún chay','Thanh',1,1),(3,1,1,'Bún cay','Cay',0,3);
    INSERT INTO restaurants(id,area_id,name,slug,description,address,latitude,longitude,price_level,is_demo) VALUES
    (1,1,'Quan A','a','','Q1',10,106,1,1),(2,1,'Quan B','b','','Q1',10,106,2,0);
    INSERT INTO menu_items VALUES (1,1,1,50000,1),(2,2,1,40000,1),(3,1,2,30000,1),(4,1,3,90000,1);");

$options = validateDiscoveryOptions(['maxPrice' => '45000', 'maxSpice' => '1', 'sort' => 'price']);
$filtered = discoverDishes($db, $options);
checkFlow(count($filtered['data']) === 2, 'Budget and spice must filter real menu data');
checkFlow($filtered['data'][0]['priceVnd'] === 30000, 'Price sorting must be applied before pagination');
checkFlow(count(discoverDishes($db, validateDiscoveryOptions(['q' => 'pho']))['data']) === 2, 'Accent-free dish search must match the requested dish');
rejectsFlow(fn() => validateDiscoveryOptions(['sort' => 'price; DROP TABLE dishes']), 'Sort injection must be rejected');
rejectsFlow(fn() => validateDiscoveryOptions(['maxSpice' => '4']), 'Invalid spice levels must be rejected');
rejectsFlow(fn() => validateDiscoveryOptions(['maxPrice' => '-1']), 'Negative budgets must be rejected');

recordNomNomTaste($db, 'alice', ['dishId' => 1, 'action' => 'like']);
$surprise = discoverDishes($db, validateDiscoveryOptions(['sort' => 'random', 'limit' => '1', 'vegetarian' => '1', 'maxPrice' => '35000', 'maxSpice' => '1']));
checkFlow(count($surprise['data']) === 1 && $surprise['data'][0]['id'] === '2', 'Surprise selection must honor diet, budget and spice before choosing');
$noSurprise = discoverDishes($db, validateDiscoveryOptions(['sort' => 'random', 'limit' => '1', 'maxPrice' => '1000']));
checkFlow($noSurprise['data'] === [], 'Surprise selection must not relax an impossible budget');
rejectsFlow(fn() => validateDiscoveryOptions(['sort' => 'random']), 'Random selection must not expose unstable pagination');
recordNomNomTaste($db, 'bob', ['dishId' => 2, 'action' => 'like']);
$favorites = listNomNomFavorites($db, 'alice');
checkFlow(count($favorites) === 1 && $favorites[0]['id'] === '1', 'Favorites must be scoped to their owner');
checkFlow($favorites[0]['priceVnd'] === 40000, 'Favorite should offer the cheapest available restaurant');
removeNomNomFavorite($db, 'bob', 1);
checkFlow(count(listNomNomFavorites($db, 'alice')) === 1, 'One user cannot remove another user favorite');
removeNomNomFavorite($db, 'alice', 1);
checkFlow(listNomNomFavorites($db, 'alice') === [], 'Removing a favorite must persist');

$group = createNomNomGroup($db, 'alice', 'Alice');
$groupCatalog = listUniqueGroupDishes($db, validateDiscoveryOptions(['limit' => '2']));
checkFlow(count($groupCatalog['data']) === 2 && $groupCatalog['meta']['total'] === 3, 'Group catalog must paginate distinct dishes, not restaurant menus');
checkFlow(count(array_unique(array_column($groupCatalog['data'], 'id'))) === 2, 'A group ballot page cannot repeat the same dish');
joinNomNomGroup($db, 'bob', $group['inviteCode'], 'Bob');
$vote = ['inviteCode' => $group['inviteCode'], 'dishId' => 2, 'vote' => 'like'];
rejectsFlow(fn() => recordNomNomGroupVote($db, 'outsider', $vote), 'Non-members cannot vote');
recordNomNomGroupVote($db, 'alice', $vote);
$summary = recordNomNomGroupVote($db, 'bob', $vote);
checkFlow($summary['memberCount'] === 2 && (int) $summary['matches'][0]['likes'] === 2, 'Both members must count toward the shared choice');
recordNomNomGroupVote($db, 'bob', $vote);
$summary = recordNomNomGroupVote($db, 'bob', array_merge($vote, ['vote' => 'skip']));
checkFlow((int) $summary['matches'][0]['likes'] === 1, 'Changing a vote must replace it, not add another vote');

$booking = createNomNomBooking($db, 'alice', validateBookingPayload([
    'restaurantId' => 1, 'guestName' => 'Alice', 'email' => 'alice@example.com',
    'bookingDate' => '2035-01-01 19:00', 'guestCount' => 2,
]));
rejectsFlow(fn() => cancelNomNomBooking($db, 'bob', $booking['id']), 'Booking cancellation must enforce ownership');
checkFlow(listNomNomBookings($db, 'alice')[0]['status'] === 'pending', 'Unauthorized cancellation must leave booking unchanged');
cancelNomNomBooking($db, 'alice', $booking['id']);
checkFlow(listNomNomBookings($db, 'alice')[0]['status'] === 'cancelled', 'Owner can cancel an upcoming booking');
rejectsFlow(fn() => cancelNomNomBooking($db, 'alice', $booking['id']), 'Repeated cancellations must not update again');
fwrite(STDOUT, "PHP product flow tests passed\n");
