<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

function assertDiscovery(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$options = validateDiscoveryOptions([
    'q' => 'chay',
    'limit' => '5',
    'offset' => '10',
    'vegetarian' => '1',
]);
assertDiscovery('chay', $options['query'], 'Discovery query should be normalized');
assertDiscovery(5, $options['limit'], 'Discovery limit should be parsed');
assertDiscovery(10, $options['offset'], 'Discovery offset should be parsed');
assertDiscovery(true, $options['vegetarian'], 'Vegetarian filter should be enabled');

try {
    validateDiscoveryOptions(['limit' => '101']);
    throw new RuntimeException('Expected invalid discovery limit to throw.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

fwrite(STDOUT, "PHP discovery tests passed\n");
