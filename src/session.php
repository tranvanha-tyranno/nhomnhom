<?php
declare(strict_types=1);

function nomnomSessionKey(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }
    return hash('sha256', session_id());
}

function nomnomCsrfToken(): string
{
    nomnomSessionKey();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function requireNomNomCsrf(array $payload): void
{
    $expected = nomnomCsrfToken();
    $provided = $payload['csrfToken'] ?? '';
    if (!is_string($provided) || $provided === '' || !hash_equals($expected, $provided)) {
        throw new InvalidArgumentException('Token bảo vệ biểu mẫu không hợp lệ.');
    }
}
