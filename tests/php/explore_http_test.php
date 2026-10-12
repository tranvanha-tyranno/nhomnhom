<?php
declare(strict_types=1);

// Run against a local installation. Each cURL handle owns an isolated cookie session.
$base = rtrim($argv[1] ?? 'http://localhost/nhomnhom', '/');
if (!in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('HTTP smoke tests must target localhost.');
}
$client = curl_init();
$other = curl_init();
curl_setopt($client, CURLOPT_COOKIEFILE, '');
curl_setopt($other, CURLOPT_COOKIEFILE, '');

function httpExplore(CurlHandle $client, string $path, int $status = 200, ?array $payload = null, string $method = 'GET'): array
{
    global $base;
    curl_setopt_array($client, [CURLOPT_URL => $base . '/api/' . $path, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload === null ? null : json_encode($payload)]);
    // Setting POSTFIELDS changes the default verb; keep the requested method explicit.
    curl_setopt($client, CURLOPT_CUSTOMREQUEST, $method);
    $raw = curl_exec($client);
    if ($raw === false) throw new RuntimeException(curl_error($client));
    $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    httpCheck(curl_getinfo($client, CURLINFO_RESPONSE_CODE) === $status, $path . ': unexpected HTTP status');
    httpCheck($body['success'] === ($status === 200), $path . ': invalid envelope');
    return $body;
}

function httpCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

try {
    $initial = httpExplore($client, 'saved.php');
    httpCheck($initial['data'] === [], 'Fresh session has no saved restaurants');
    $token = $initial['csrfToken'];
    $search = httpExplore($client, 'restaurants.php?vegetarian=1&maxPrice=100000&sort=price_asc&pageSize=5');
    httpCheck(count($search['data']) > 0, 'Seed contains affordable vegetarian restaurants');
    $previous = -1;
    foreach ($search['data'] as $place) {
        httpCheck($place['minPriceVnd'] >= $previous, 'Prices sorted ascending');
        $previous = $place['minPriceVnd'];
        $detail = httpExplore($client, 'restaurant.php?id=' . $place['id'])['data'];
        httpCheck(count(array_filter($detail['menu'], static fn(array $dish): bool => $dish['isVegetarian'] && $dish['priceVnd'] <= 100000)) > 0, 'Matching menu item satisfies combined filters');
    }
    $id = (int) $search['data'][0]['id'];
    httpExplore($client, 'saved.php', 400, ['restaurantId' => $id, 'action' => 'save'], 'POST');
    httpExplore($client, 'saved.php', 400, ['restaurantId' => $id, 'action' => 'save', 'csrfToken' => []], 'POST');
    $payload = ['restaurantId' => $id, 'action' => 'save', 'csrfToken' => $token];
    httpCheck(count(httpExplore($client, 'saved.php', 200, $payload, 'POST')['data']) === 1, 'Save restaurant');
    httpCheck(count(httpExplore($client, 'saved.php', 200, $payload, 'POST')['data']) === 1, 'Duplicate save is idempotent');
    httpCheck(count(httpExplore($client, 'saved.php')['data']) === 1, 'Saved restaurant persists between requests');
    $otherSession = httpExplore($other, 'saved.php');
    httpCheck($otherSession['data'] === [], 'Other session cannot read saved list');
    httpExplore($other, 'saved.php', 400, $payload, 'POST');
    httpExplore($other, 'saved.php', 200, ['restaurantId' => $id, 'action' => 'remove', 'csrfToken' => $otherSession['csrfToken']], 'POST');
    httpCheck(count(httpExplore($client, 'saved.php')['data']) === 1, 'Other session cannot remove saved restaurant');
    foreach (['maxPrice=-1', 'minRating=9', 'sort=unsafe', 'vegetarian[]=1'] as $invalid) {
        httpExplore($client, 'restaurants.php?' . $invalid, 400);
    }
    httpExplore($client, 'restaurant.php?id[]=1', 400);
    httpExplore($client, 'restaurant.php?id=0', 400);
    httpExplore($client, 'restaurant.php?id=999999999', 404);
    httpExplore($client, 'restaurant.php?id=' . $id, 405, [], 'POST');
    httpExplore($client, 'saved.php', 405, null, 'DELETE');
    $random = httpExplore($client, 'restaurants.php?maxPrice=100000&vegetarian=1&sort=random&pageSize=1');
    httpCheck(count($random['data']) === 1, 'Random pick returns one filtered restaurant');
    httpCheck(httpExplore($client, 'restaurants.php?q=bun%20bo&pageSize=1')['pagination']['total'] > 0, 'Existing accent-free search still works');
    fwrite(STDOUT, "PHP exploration HTTP tests passed: {$base}\n");
} finally {
    if (isset($token, $id)) {
        httpExplore($client, 'saved.php', 200, ['restaurantId' => $id, 'action' => 'remove', 'csrfToken' => $token], 'POST');
    }
    curl_close($client);
    curl_close($other);
}
