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

$random = validateDiscoveryOptions(['sort' => 'random', 'limit' => '1', 'maxPrice' => '50000', 'vegetarian' => '1']);
assertDiscovery('random', $random['sort'], 'A surprise meal must support server-side random selection');
assertDiscovery(50000, $random['maxPrice'], 'Random selection must preserve the budget');
assertDiscovery(true, $random['vegetarian'], 'Random selection must preserve dietary restrictions');
try {
    validateDiscoveryOptions(['sort' => 'random; DROP TABLE dishes']);
    throw new RuntimeException('Expected unsafe sort to fail.');
} catch (InvalidArgumentException) {
    // Only allowlisted sort choices may reach SQL.
}
fwrite(STDOUT, "PHP discovery tests passed\n");
