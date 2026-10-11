<?php
declare(strict_types=1);

function databasePath(): string
{
    $configured = getenv('NHOMNHOM_DB_PATH');
    if (is_string($configured) && $configured !== '') {
        return $configured;
    }
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'nhomnhom.sqlite';
}

function databaseDriver(): string
{
    $driver = strtolower((string) (getenv('NHOMNHOM_DB_DRIVER') ?: 'sqlite'));
    if (!in_array($driver, ['sqlite', 'mysql'], true)) {
        throw new RuntimeException('NHOMNHOM_DB_DRIVER phải là sqlite hoặc mysql.');
    }
    return $driver;
}

function isMysqlConnection(PDO $db): bool
{
    return $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
}

function openDatabase(): PDO
{
    if (databaseDriver() === 'mysql') {
        $host = (string) (getenv('NHOMNHOM_DB_HOST') ?: '127.0.0.1');
        $port = (string) (getenv('NHOMNHOM_DB_PORT') ?: '3306');
        $name = (string) (getenv('NHOMNHOM_DB_NAME') ?: 'nhomnhom');
        $user = (string) (getenv('NHOMNHOM_DB_USER') ?: 'root');
        $password = (string) (getenv('NHOMNHOM_DB_PASSWORD') ?: '');
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $host . $port . $name)) {
            throw new RuntimeException('Cấu hình MySQL không hợp lệ.');
        }
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $db = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        ensureNomNomFeatureTables($db);
        return $db;
    }
    $path = databasePath();
    if (!is_file($path)) {
        throw new RuntimeException('Không tìm thấy CSDL. Hãy đặt data/nhomnhom.sqlite vào thư mục dự án.');
    }

    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
    ensureNomNomFeatureTables($db);
    return $db;
}

function ensureNomNomFeatureTables(PDO $db): void
{
    if (isMysqlConnection($db)) {
        ensureNomNomFeatureTablesMysql($db);
        return;
    }
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS nomnom_profiles (
    session_key TEXT PRIMARY KEY,
    display_name TEXT NOT NULL DEFAULT 'Bạn' CHECK(length(display_name) BETWEEN 1 AND 80),
    vegetarian_only INTEGER NOT NULL DEFAULT 0 CHECK(vegetarian_only IN (0, 1)),
    max_price_vnd INTEGER NOT NULL DEFAULT 0 CHECK(max_price_vnd BETWEEN 0 AND 100000000),
    travel_mode TEXT NOT NULL DEFAULT 'walking' CHECK(travel_mode IN ('driving', 'walking', 'bicycling', 'transit')),
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS nomnom_taste_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_key TEXT NOT NULL,
    dish_id INTEGER NOT NULL REFERENCES dishes(id) ON DELETE CASCADE,
    action TEXT NOT NULL CHECK(action IN ('like', 'skip')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(session_key, dish_id)
);
CREATE INDEX IF NOT EXISTS idx_nomnom_taste_session ON nomnom_taste_actions(session_key, action);
CREATE TABLE IF NOT EXISTS nomnom_taste_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_key TEXT NOT NULL,
    invite_code TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'closed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS nomnom_group_members (
    group_id INTEGER NOT NULL REFERENCES nomnom_taste_groups(id) ON DELETE CASCADE,
    session_key TEXT NOT NULL,
    display_name TEXT NOT NULL CHECK(length(display_name) BETWEEN 1 AND 80),
    joined_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(group_id, session_key)
);
CREATE TABLE IF NOT EXISTS nomnom_group_votes (
    group_id INTEGER NOT NULL REFERENCES nomnom_taste_groups(id) ON DELETE CASCADE,
    session_key TEXT NOT NULL,
    dish_id INTEGER NOT NULL REFERENCES dishes(id) ON DELETE CASCADE,
    vote TEXT NOT NULL CHECK(vote IN ('like', 'skip')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(group_id, session_key, dish_id)
);
CREATE TABLE IF NOT EXISTS nomnom_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_key TEXT NOT NULL,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id),
    guest_name TEXT NOT NULL CHECK(length(guest_name) BETWEEN 1 AND 100),
    email TEXT NOT NULL CHECK(length(email) BETWEEN 3 AND 254),
    booking_date TEXT NOT NULL,
    guest_count INTEGER NOT NULL CHECK(guest_count BETWEEN 1 AND 20),
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'confirmed', 'cancelled')),
    coupon_code TEXT,
    note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_nomnom_bookings_session ON nomnom_bookings(session_key, created_at DESC);
CREATE TABLE IF NOT EXISTS nomnom_coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    code TEXT NOT NULL UNIQUE,
    discount_percent INTEGER NOT NULL CHECK(discount_percent BETWEEN 1 AND 90),
    max_uses INTEGER NOT NULL DEFAULT 100 CHECK(max_uses > 0),
    used_count INTEGER NOT NULL DEFAULT 0 CHECK(used_count >= 0),
    expires_at TEXT,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'paused', 'expired'))
);
CREATE TABLE IF NOT EXISTS nomnom_dish_elo (
    dish_id INTEGER PRIMARY KEY REFERENCES dishes(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL DEFAULT 1200 CHECK(rating BETWEEN 400 AND 2400),
    wins INTEGER NOT NULL DEFAULT 0 CHECK(wins >= 0),
    losses INTEGER NOT NULL DEFAULT 0 CHECK(losses >= 0),
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL);
}

function ensureNomNomFeatureTablesMysql(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS nomnom_profiles (
    session_key CHAR(64) PRIMARY KEY, display_name VARCHAR(80) NOT NULL DEFAULT 'Bạn',
    vegetarian_only TINYINT NOT NULL DEFAULT 0, max_price_vnd BIGINT NOT NULL DEFAULT 0,
    travel_mode VARCHAR(20) NOT NULL DEFAULT 'walking', updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_taste_actions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, session_key CHAR(64) NOT NULL, dish_id BIGINT NOT NULL,
    action VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_taste_session_dish (session_key, dish_id), INDEX idx_taste_session (session_key, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_taste_groups (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, owner_key CHAR(64) NOT NULL, invite_code CHAR(8) NOT NULL UNIQUE,
    status VARCHAR(10) NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_group_members (
    group_id BIGINT NOT NULL, session_key CHAR(64) NOT NULL, display_name VARCHAR(80) NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(group_id, session_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_group_votes (
    group_id BIGINT NOT NULL, session_key CHAR(64) NOT NULL, dish_id BIGINT NOT NULL, vote VARCHAR(10) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(group_id, session_key, dish_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_bookings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, session_key CHAR(64) NOT NULL, restaurant_id BIGINT NOT NULL,
    guest_name VARCHAR(100) NOT NULL, email VARCHAR(254) NOT NULL, booking_date DATETIME NOT NULL,
    guest_count INT NOT NULL, status VARCHAR(15) NOT NULL DEFAULT 'pending', coupon_code VARCHAR(32), note VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_booking_session(session_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_coupons (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, restaurant_id BIGINT NOT NULL, code VARCHAR(32) NOT NULL UNIQUE,
    discount_percent INT NOT NULL, max_uses INT NOT NULL DEFAULT 100, used_count INT NOT NULL DEFAULT 0,
    expires_at DATETIME NULL, status VARCHAR(10) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS nomnom_dish_elo (
    dish_id BIGINT PRIMARY KEY, rating INT NOT NULL DEFAULT 1200, wins INT NOT NULL DEFAULT 0,
    losses INT NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
}
