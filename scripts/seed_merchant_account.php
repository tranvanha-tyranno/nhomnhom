<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/demo_seed.php';

$passcode = getenv('NHOMNHOM_DEMO_PASSCODE');
if (!is_string($passcode) || $passcode === '') {
    $passcode = $argv[1] ?? 'NhomNhom@2026';
}

$db = openDatabase();
$hash = password_hash($passcode, PASSWORD_DEFAULT);
$merchant = upsertDemoAccount($db, 'quan@nhomnhom.vn', 'Chủ quán Quận 7', $hash, 'merchant');

$slugs = ['verified-pho-phu-vuong-q7', 'verified-com-tam-phuc-loc-tho-q7'];
$ignore = isMysqlConnection($db) ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
$find = $db->prepare('SELECT id FROM restaurants WHERE slug = ?');
$link = $db->prepare("{$ignore} nomnom_merchant_restaurants(account_id, restaurant_id) VALUES (?, ?)");
$linked = 0;
foreach ($slugs as $slug) {
    $find->execute([$slug]);
    $rid = (int) $find->fetchColumn();
    if ($rid > 0) {
        $link->execute([$merchant['id'], $rid]);
        $linked++;
    }
}

fwrite(STDOUT, sprintf(
    "Seeded merchant account %s (id=%d), linked %d restaurants.\n",
    $merchant['email'],
    $merchant['id'],
    $linked
));
