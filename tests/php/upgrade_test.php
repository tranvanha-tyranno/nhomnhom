<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout.php';

function assertUpgrade(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertUpgradeThrows(callable $fn, string $message): void
{
    try {
        $fn();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException($message);
}

$db = openDatabase();

// 1. Restaurant search filter options (priceLevel, sort, verifiedOnly)
$opts = parseSearchOptions([
    'q' => 'pho',
    'page' => '1',
    'pageSize' => '6',
    'priceLevel' => '2',
    'sort' => 'rating',
    'verifiedOnly' => '1',
]);
assertUpgrade($opts['priceLevel'] === 2, 'priceLevel should parse as integer 2');
assertUpgrade($opts['sort'] === 'rating', 'sort should parse as rating');
assertUpgrade($opts['verifiedOnly'] === true, 'verifiedOnly should parse as true');

assertUpgradeThrows(fn() => parseSearchOptions(['priceLevel' => '5']), 'Invalid priceLevel must throw');
assertUpgradeThrows(fn() => parseSearchOptions(['sort' => 'hacked']), 'Invalid sort must throw');
assertUpgradeThrows(fn() => parseSearchOptions(['verifiedOnly' => 'yes']), 'Invalid verifiedOnly must throw');

$filtered = findRestaurants($db, [
    'query' => '',
    'page' => 1,
    'pageSize' => 10,
    'priceLevel' => 1,
    'sort' => 'price_asc',
    'verifiedOnly' => false,
]);
assertUpgrade(isset($filtered['data']) && is_array($filtered['data']), 'Filtered search must return data array');
foreach ($filtered['data'] as $place) {
    assertUpgrade($place['priceLevel'] === 1, 'Filtered restaurants must match priceLevel=1');
}

// 2. Restaurant detail + menu endpoint logic
assertUpgrade(function_exists('findRestaurantDetail'), 'findRestaurantDetail must exist');
$firstList = findRestaurants($db, ['query' => '', 'page' => 1, 'pageSize' => 1]);
assertUpgrade(count($firstList['data']) === 1, 'Database should have at least one active restaurant');
$firstId = (int) $firstList['data'][0]['id'];
$detail = findRestaurantDetail($db, $firstId);
assertUpgrade(is_array($detail), 'findRestaurantDetail should return array for active restaurant');
assertUpgrade((int) $detail['id'] === $firstId, 'Detail id must match requested id');
assertUpgrade(isset($detail['menu']) && is_array($detail['menu']), 'Detail must include menu array');
assertUpgrade(array_key_exists('minPriceVnd', $detail), 'Detail must include minPriceVnd');
assertUpgrade(findRestaurantDetail($db, 99999999) === null, 'Unknown restaurant must return null');

// 3. Top dishes for insights
assertUpgrade(function_exists('nomNomTopDishes'), 'nomNomTopDishes must exist');
$topDishes = nomNomTopDishes($db, 5);
assertUpgrade(is_array($topDishes) && count($topDishes) > 0, 'nomNomTopDishes should return dishes');
assertUpgrade(isset($topDishes[0]['eloRating'], $topDishes[0]['minPriceVnd']), 'Top dish must include eloRating and minPriceVnd');

// 4. Upgraded layout (theme toggle, toast container, mobile bottom nav)
ob_start();
renderPageHeader($db, 'Trang thử', 'index.php');
renderPageFooter('index.php');
$html = ob_get_clean();
assertUpgrade(str_contains($html, 'id="theme-toggle"'), 'Header must include Dark/Light theme toggle');
assertUpgrade(str_contains($html, 'id="toast-region"'), 'Footer must include accessible toast notification region');
assertUpgrade(str_contains($html, 'class="mobileBottomNav"'), 'Footer must include mobile bottom navigation bar');

fwrite(STDOUT, "PHP upgrade tests passed\n");
