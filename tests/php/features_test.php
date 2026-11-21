<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

function assertFeature(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$profile = validateProfilePayload([
    'displayName' => 'Lan',
    'vegetarianOnly' => true,
    'maxPriceVnd' => 120000,
    'travelMode' => 'walking',
]);
assertFeature('Lan', $profile['displayName'], 'Profile name should be accepted');
assertFeature(true, $profile['vegetarianOnly'], 'Vegetarian preference should be accepted');

$taste = validateTastePayload(['dishId' => 12, 'action' => 'like']);
assertFeature(12, $taste['dishId'], 'Taste dish ID should be validated');
assertFeature('like', $taste['action'], 'Taste action should be validated');

$booking = validateBookingPayload([
    'restaurantId' => 12,
    'guestName' => 'Lan',
    'email' => 'lan@example.com',
    'bookingDate' => '2030-01-10 19:00',
    'guestCount' => 4,
]);
assertFeature(4, $booking['guestCount'], 'Booking guest count should be validated');

try {
    validateTastePayload(['dishId' => 0, 'action' => 'like']);
    throw new RuntimeException('Expected invalid taste payload to throw.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

fwrite(STDOUT, "PHP feature tests passed\n");
