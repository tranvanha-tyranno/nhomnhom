<?php
declare(strict_types=1);

function safeNomNomReturn(mixed $value): string
{
    if (!is_string($value) || !preg_match('/^[A-Za-z0-9_?=&.\/-]+$/', $value)
        || str_contains($value, '..') || str_starts_with($value, '//')) {
        return 'discover.php';
    }
    return $value;
}

function validateAuthPayload(array $input): array
{
    $action = (string) ($input['action'] ?? 'login');
    if (!in_array($action, ['login', 'register'], true)) {
        throw new InvalidArgumentException('Hành động tài khoản không hợp lệ.');
    }
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        throw new InvalidArgumentException('Email không hợp lệ.');
    }
    $password = (string) ($input['password'] ?? '');
    if (strlen($password) < 8 || strlen($password) > 200) {
        throw new InvalidArgumentException('Mật khẩu phải dài từ 8 đến 200 ký tự.');
    }
    $displayName = trim((string) ($input['displayName'] ?? ''));
    if ($action === 'register' && ($displayName === '' || strlen($displayName) > 80)) {
        throw new InvalidArgumentException('Tên hiển thị phải từ 1 đến 80 ký tự.');
    }
    $role = strtolower(trim((string) ($input['role'] ?? 'customer')));
    if ($role === '') {
        $role = 'customer';
    }
    if ($action === 'register' && !in_array($role, ['customer', 'merchant'], true)) {
        throw new InvalidArgumentException('Loại tài khoản chỉ hỗ trợ Khách ăn uống hoặc Chủ cửa hàng.');
    }
    return compact('action', 'email', 'password', 'displayName', 'role');
}

function createNomNomAccount(PDO $db, array $payload): array
{
    $exists = $db->prepare('SELECT id FROM nomnom_accounts WHERE email=?');
    $exists->execute([$payload['email']]);
    if ($exists->fetchColumn()) {
        throw new InvalidArgumentException('Email này đã được đăng ký.');
    }
    $role = in_array(($payload['role'] ?? 'customer'), ['customer', 'merchant'], true)
        ? (string) $payload['role']
        : 'customer';
    $statement = $db->prepare('INSERT INTO nomnom_accounts(email, password_hash, display_name, role) VALUES (?, ?, ?, ?)');
    $statement->execute([$payload['email'], password_hash($payload['password'], PASSWORD_DEFAULT), $payload['displayName'], $role]);
    $account = [
        'id' => (int) $db->lastInsertId(),
        'email' => $payload['email'],
        'displayName' => $payload['displayName'],
        'role' => $role,
    ];
    startNomNomAccountSession($account);
    return $account;
}

function loginNomNomAccount(PDO $db, array $payload): array
{
    $statement = $db->prepare("SELECT id, email, password_hash, display_name, role FROM nomnom_accounts WHERE email=? AND status='active'");
    $statement->execute([$payload['email']]);
    $row = $statement->fetch();
    if (!$row || !password_verify($payload['password'], (string) $row['password_hash'])) {
        throw new InvalidArgumentException('Email hoặc mật khẩu không đúng.');
    }
    $account = [
        'id' => (int) $row['id'],
        'email' => (string) $row['email'],
        'displayName' => (string) $row['display_name'],
        'role' => (string) ($row['role'] ?? 'customer'),
    ];
    startNomNomAccountSession($account);
    return $account;
}

function startNomNomAccountSession(array $account): void
{
    nomnomSessionKey();
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['account_id'] = $account['id'];
    $_SESSION['account_email'] = $account['email'];
    $_SESSION['account_name'] = $account['displayName'];
    $_SESSION['account_role'] = (string) ($account['role'] ?? 'customer');
    nomnomCsrfToken();
}

function currentNomNomAccount(PDO $db): ?array
{
    nomnomSessionKey();
    $id = (int) ($_SESSION['account_id'] ?? 0);
    if ($id < 1) return null;
    $statement = $db->prepare("SELECT id, email, display_name AS displayName, role FROM nomnom_accounts WHERE id=? AND status='active'");
    $statement->execute([$id]);
    $account = $statement->fetch();
    return $account ? [
        'id' => (int) $account['id'],
        'email' => (string) $account['email'],
        'displayName' => (string) $account['displayName'],
        'role' => (string) ($account['role'] ?? 'customer'),
    ] : null;
}

function isNomNomMerchant(?array $account): bool
{
    return $account !== null && in_array((string) ($account['role'] ?? 'customer'), ['merchant', 'admin'], true);
}

function requireNomNomMerchant(PDO $db): array
{
    $account = requireNomNomAccount($db);
    if (!isNomNomMerchant($account)) {
        http_response_code(403);
        throw new InvalidArgumentException('Tài khoản hiện tại không có quyền Cửa hàng.');
    }
    return $account;
}

function requireNomNomAccount(PDO $db): array
{
    $account = currentNomNomAccount($db);
    if (!$account) {
        header('Location: login.php?return=' . rawurlencode($_SERVER['REQUEST_URI'] ?? 'profile.php'));
        exit;
    }
    return $account;
}

function logoutNomNomAccount(): void
{
    nomnomSessionKey();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
