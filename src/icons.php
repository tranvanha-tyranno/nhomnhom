<?php
declare(strict_types=1);

function uiIcon(string $name): string
{
    $paths = [
        'brand' => '<path d="M7 3v6m3-6v6M5 3v6a3 3 0 0 0 6 0V3M8 12v9m9-18c-3 3-3 8 0 9V3Zm0 9v9"/>',
        'home' => '<path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/>',
        'discover' => '<circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5Z"/>',
        'group' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-2-4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 5h3"/>',
        'trend' => '<path d="m3 17 6-6 4 4 8-10m-6 0h6v6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/>',
        'moon' => '<path d="M20.5 14.5A8.5 8.5 0 1 1 9.5 3.5a7 7 0 0 0 11 11Z"/>',
        'noodle' => '<path d="M4 12h16a8 8 0 0 1-16 0Z"/><path d="M8 19h8M7 9V5m4 4V4m4 5V6"/>',
        'soup' => '<path d="M3 12h18a8.5 8.5 0 0 1-17 0Z"/><path d="M9 19h6M8 8c0-1.5 1.5-2 1.5-3.5M14 8c0-1.5 1.5-2 1.5-3.5"/>',
        'rice' => '<path d="M4 13h16a8 8 0 0 1-16 0Z"/><path d="M6 13c0-3 2.7-5.5 6-5.5s6 2.5 6 5.5"/><path d="M9 20h6"/>',
        'leaf' => '<path d="M19 5c-6.5 0-12 3.5-13 11 5.5 1 11-2 13-11Z"/><path d="M5 19c2-2.5 4.5-4.5 8-6"/>',
        'bread' => '<path d="M4 14a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v2a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-2Z"/><path d="M9 9v3m6-3v3"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v3m0 12v3M3 12h3m12 0h3"/><circle cx="12" cy="12" r="2.2"/>',
        'share' => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.3 10.9 7.4-4.8m-7.4 7 7.4 4.8"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'cards' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h5"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v18H6.5A2.5 2.5 0 0 1 4 18.5v-13Z"/><path d="M8 7h8M8 11h6"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9Z"/>',
        'check' => '<path d="m5 12 4.5 4.5L19 7"/>',
    ];
    return '<svg class="uiIcon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['spark']) . '</svg>';
}
