<?php
declare(strict_types=1);
require_once __DIR__ . '/icons.php';

function htmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderDocumentStart(string $title, string $bodyClass = ''): void
{
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="theme-color" content="#174b3c"><meta name="description" content="NhomNhom — khám phá món hợp gu, xem thực đơn quán, chọn món cùng bạn bè và chỉ đường Google Maps.">';
    echo '<title>' . htmlEscape($title) . ' · NhomNhom</title>';
    echo '<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap">';
    echo '<script>(function(){try{var t=localStorage.getItem("nhomnhom-theme");if(!t&&window.matchMedia("(prefers-color-scheme:dark)").matches)t="dark";if(t==="dark")document.documentElement.setAttribute("data-theme","dark");}catch(e){}})();</script>';
    echo '<link rel="stylesheet" href="assets/app.css?v=20261119"><link rel="stylesheet" href="assets/redesign.css?v=20261119"><link rel="stylesheet" href="assets/upgrade.css?v=20261119">';
    echo '<script src="assets/client.js?v=20261119" defer></script><script src="assets/ui.js?v=20261119" defer></script></head><body class="' . $bodyClass . '">';
    echo '<a class="skipLink" href="#main-content">Đến nội dung chính</a>';
}

function renderBrand(): void
{
    echo '<a class="brandLockup" href="index.php" aria-label="NhomNhom trang chủ"><span class="brandMark">' . uiIcon('brand') . '</span><span>nhomnhom<span class="brandDot">.</span></span></a>';
}

function renderPageHeader(PDO $db, string $title, string $active = ''): void
{
    $account = currentNomNomAccount($db);
    $links = [
        'index.php' => ['home', 'Trang chủ'],
        'discover.php' => ['discover', 'Khám phá'],
        'favorites.php' => ['heart', 'Yêu thích'],
        'groups.php' => ['group', 'Nhóm bạn'],
        'bookings.php' => ['calendar', 'Đặt bàn'],
        'insights.php' => ['trend', 'Xu hướng'],
        'profile.php' => ['user', 'Khẩu vị'],
    ];
    renderDocumentStart($title);
    echo '<div class="appShell"><div class="workspace"><header class="siteNav"><div class="topbarTitle"><button class="menuToggle" type="button" aria-label="Mở điều hướng" aria-expanded="false" aria-controls="site-sidebar">' . uiIcon('menu') . '</button>';
    renderBrand();
    echo '</div><nav class="siteSidebar navLinks" id="site-sidebar" aria-label="Điều hướng chính">';
    foreach ($links as $href => [$icon, $label]) {
        $isActive = $active === $href || ($active === '' && $href === 'index.php');
        echo '<a class="' . ($isActive ? 'isActive' : '') . '" ' . ($isActive ? 'aria-current="page" ' : '') . 'href="' . $href . '">' . uiIcon($icon) . '<span>' . $label . '</span></a>';
    }
    echo '</nav><div class="navAccount">';
    echo '<button class="themeToggle" id="theme-toggle" type="button" aria-label="Chuyển giao diện sáng tối" aria-pressed="false" title="Chuyển chế độ Sáng / Tối"><span class="iconSun">' . uiIcon('sun') . '</span><span class="iconMoon">' . uiIcon('moon') . '</span></button>';
    if ($account) {
        echo '<a class="accountChip" href="profile.php"><span class="avatar">' . uiIcon('user') . '</span><span>' . htmlEscape($account['displayName']) . '</span></a><a class="navLogout" href="logout.php">Đăng xuất</a>';
    } else {
        echo '<a class="navLogin" href="login.php">Đăng nhập</a><a class="navSignup" href="register.php">Đăng ký ' . uiIcon('arrow') . '</a>';
    }
    echo '</div></header><main class="pageContent" id="main-content" tabindex="-1">';
}

function renderPageFooter(string $active = ''): void
{
    $bottomLinks = [
        'index.php' => ['home', 'Trang chủ'],
        'discover.php' => ['discover', 'Khám phá'],
        'favorites.php' => ['heart', 'Yêu thích'],
        'groups.php' => ['group', 'Nhóm'],
        'bookings.php' => ['calendar', 'Đặt bàn'],
    ];
    echo '</main><footer class="siteFooter"><span>© ' . date('Y') . ' NhomNhom<span class="footerDot">·</span>Ăn ngon, gần nhau hơn.</span><a href="discover.php">Khám phá một món mới ↗</a></footer>';
    echo '<nav class="mobileBottomNav" aria-label="Điều hướng nhanh trên di động">';
    foreach ($bottomLinks as $href => [$icon, $label]) {
        $isActive = $active === $href || ($active === '' && $href === 'index.php');
        echo '<a class="' . ($isActive ? 'isActive' : '') . '" ' . ($isActive ? 'aria-current="page" ' : '') . 'href="' . $href . '">' . uiIcon($icon) . '<span>' . $label . '</span></a>';
    }
    echo '</nav>';
    echo '<div id="toast-region" class="toastRegion" role="status" aria-live="polite" aria-atomic="true"></div>';
    echo '</div></div></body></html>';
}

function renderAuthHeader(string $title, bool $register = false): void
{
    if (function_exists('nomnomCsrfToken') && !headers_sent()) {
        nomnomCsrfToken();
    }
    renderDocumentStart($title, 'authBody');
    echo '<main class="authShell" id="main-content"><section class="authStory">';
    renderBrand();
    echo '<div class="authStoryContent"><span class="storyEyebrow"><span class="statusDot"></span> Một nơi cho những bữa ngon</span><h1>Gần nhau hơn.<br>Qua từng<br><span>bữa ngon.</span></h1><p>Món bạn thích. Hội bạn thân.<br>Một cuộc hẹn ngon lành bắt đầu từ đây.</p><div class="storyMessages"><div class="storyMessage"><span class="messageAvatar lavender">L</span><div><b>Linh <small>vừa xong</small></b><p>Tối nay mình ăn gì nhỉ?</p></div></div><div class="storyMessage"><span class="messageAvatar mint">M</span><div><b>Minh <small>vừa xong</small></b><p>Đã tìm được món cả hội cùng thích rồi!</p></div></div><span class="typingHint"><i>•••</i> Một bữa ngon đang được lên kế hoạch</span></div></div><div class="storyFooter"><span>Chọn món dễ hơn. Gặp nhau nhiều hơn.</span><span>Hẹn nhau ăn ngon</span></div></section>';
    echo '<section class="authFormSide"><div class="authTopline"><span>' . ($register ? 'Hành trình ngon bắt đầu từ đây' : 'Chào mừng bạn trở lại') . '</span><a href="index.php" aria-label="Về trang chủ">↗</a></div><div class="authFormWrap">';
}

function renderAuthFooter(): void
{
    echo '</div><footer class="authFooter"><span>NhomNhom / Khám phá ẩm thực</span><span>Made for good taste</span></footer></section></main></body></html>';
}
