<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/verified_seed.php';

$db = openDatabase();
$summary = seedVerifiedRestaurants($db);

fwrite(STDOUT, sprintf(
    "Seeded %d verified Google Maps restaurants and %d menu items.\n",
    $summary['restaurants'],
    $summary['menuItems']
));
