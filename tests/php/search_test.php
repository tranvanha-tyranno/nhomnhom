<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

assertSameValue('bun bo hue', normalizeSearchText('Bún bò Huế'), 'Vietnamese accents should be normalized');
assertSameValue('https://www.google.com/maps/dir/?api=1&destination=10.7758%2C106.7019&travelmode=walking',
    buildDirectionsUrl(10.7758, 106.7019, 'walking'), 'Directions URL should be encoded consistently');

$places = [
    ['name' => 'Bún Bò Huế Cô Ba', 'cuisine' => 'Bún bò Huế', 'description' => '', 'address' => '', 'tags' => ['bun bo', 'hue'], 'rating' => 4.8],
    ['name' => 'Cơm Chay An Lành', 'cuisine' => 'Món chay', 'description' => '', 'address' => '', 'tags' => ['chay'], 'rating' => 4.7],
];
$results = searchPlacesInMemory($places, 'bun bo');
assertSameValue('Bún Bò Huế Cô Ba', $results[0]['name'], 'Search should match Vietnamese text without accents');

fwrite(STDOUT, "PHP search tests passed\n");
