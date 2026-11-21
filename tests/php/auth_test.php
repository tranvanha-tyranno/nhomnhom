<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

function assertAuth(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$payload = validateAuthPayload([
    'action' => 'register',
    'displayName' => 'Lan Nguyễn',
    'email' => 'lan@example.com',
    'password' => 'MatKhau!123',
]);
assertAuth('lan@example.com', $payload['email'], 'Email should be normalized');
assertAuth('Lan Nguyễn', $payload['displayName'], 'Display name should be retained');

try {
    validateAuthPayload(['action' => 'login', 'email' => 'lan@example.com', 'password' => 'short']);
    throw new RuntimeException('Expected a short password to be rejected.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

$hash = password_hash('MatKhau!123', PASSWORD_DEFAULT);
assertAuth(true, password_verify('MatKhau!123', $hash), 'Password hash should verify');
assertAuth(false, password_verify('wrong-password', $hash), 'Wrong password should fail');
assertAuth('discover.php', safeNomNomReturn('//example.com'), 'Protocol-relative redirects must be rejected');
assertAuth('discover.php', safeNomNomReturn(['invalid']), 'Array redirects must be rejected');
assertAuth('/nhomnhom/bookings.php?restaurant=1', safeNomNomReturn('/nhomnhom/bookings.php?restaurant=1'), 'Local booking selection must survive login');

fwrite(STDOUT, "PHP auth tests passed\n");
