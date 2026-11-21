<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout.php';

function assertMarkup(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$db = new PDO('sqlite::memory:');
ob_start();
renderPageHeader($db, '<Trang thử>', 'discover.php');
renderPageFooter();
$page = ob_get_clean();
assertMarkup(str_contains($page, '&lt;Trang thử&gt;'), 'Page titles must remain escaped');
assertMarkup(str_contains($page, 'aria-current="page" href="discover.php"'), 'Active navigation must be announced');
assertMarkup(str_contains($page, 'href="#main-content"'), 'Keyboard users need a skip link');
assertMarkup(str_contains($page, 'aria-controls="site-sidebar"'), 'Mobile menu must identify its controlled navigation');
assertMarkup(str_contains($page, 'id="main-content"'), 'The main content landmark must exist');

ob_start();
renderAuthHeader('Đăng nhập', false);
renderAuthFooter();
$auth = ob_get_clean();
assertMarkup(str_contains($auth, 'class="authShell"'), 'Authentication needs its own split layout');
assertMarkup(!str_contains($auth, 'id="site-sidebar"'), 'Authentication should have focused navigation');
assertMarkup(str_contains($auth, 'href="index.php"'), 'Authentication must provide a route home');
fwrite(STDOUT, "PHP layout tests passed\n");
