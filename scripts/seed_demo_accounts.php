<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/demo_seed.php';

$passcode = getenv('NHOMNHOM_DEMO_PASSCODE');
if (!is_string($passcode) || $passcode === '') {
    $passcode = $argv[1] ?? 'NhomNhom@2026';
}

$db = openDatabase();
$summary = seedNomNomDemoAccounts($db, $passcode);

fwrite(STDOUT, sprintf(
    "Seeded %d accounts, %d bookings, %d likes (%d skips), %d coupons, groups: %s\n",
    $summary['accounts'],
    $summary['bookings'],
    $summary['likes'],
    $summary['skips'],
    $summary['coupons'],
    implode(', ', $summary['groups'])
));
