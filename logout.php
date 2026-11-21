<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
logoutNomNomAccount();
header('Location: index.php');
exit;
