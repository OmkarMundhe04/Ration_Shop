<?php
/**
 * Online Ration Shop - Universal Multi-Database Driver
 * 
 * Auto-Detects & Connects to:
 * 1. Render PostgreSQL Database (postgres:// or postgresql:// via DATABASE_URL)
 * 2. Cloud MySQL Database (mysql:// via DATABASE_URL, JAWSDB_URL, CLEARDB_URL)
 * 3. Individual Environment Variables (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT)
 * 4. Local MySQL / XAMPP defaults (localhost:3306)
 * 5. Zero-Configuration SQLite Auto-Fallback (Ensures 100% instant uptime)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = null;
$conn = null;
$db_driver = 'unknown';
$db_error_message = '';

// 1. Check for single Database URL (Render PostgreSQL, Cloud MySQL, etc.)
$db_url = getenv('DATABASE_URL') ?: getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('MYSQL_URL');

// 2. PostgreSQL Connection (Render Default)
if ($db_url && (str_starts_with($db_url, 'postgres://') || str_starts_with($db_url, 'postgresql://'))) {
    try {
        $url_parts = parse_url($db_url);
        $pg_host = $url_parts['host'] ?? 'localhost';
        $pg_port = $url_parts['port'] ?? 5432;
        $pg_user = $url_parts['user'] ?? '';
        $pg_pass = $url_parts['pass'] ?? '';
        $pg_name = ltrim($url_parts['path'] ?? '', '/');

        $dsn = "pgsql:host=$pg_host;port=$pg_port;dbname=$pg_name";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ];

        $pdo = new PDO($dsn, $pg_user, $pg_pass, $options);
        $db_driver = 'pgsql';
    } catch (Exception $e) {
        $db_error_message = "PostgreSQL Error: " . $e->getMessage();
        error_log($db_error_message);
    }
}

// 3. MySQL Connection (Cloud or Local XAMPP)
if (!$pdo) {
    $db_host = 'localhost';
    $db_port = 3306;
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'ration_shop';

    if ($db_url && str_starts_with($db_url, 'mysql://')) {
        $url_parts = parse_url($db_url);
        if ($url_parts) {
            $db_host = $url_parts['host'] ?? $db_host;
            $db_port = $url_parts['port'] ?? $db_port;
            $db_user = $url_parts['user'] ?? $db_user;
            $db_pass = $url_parts['pass'] ?? $db_pass;
            $db_name = ltrim($url_parts['path'] ?? $db_name, '/');
        }
    } else {
        $db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: $db_host;
        $db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: $db_port;
        $db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: $db_user;
        $db_pass = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: $db_pass;
        $db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: $db_name;
    }

    try {
        $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 3
        ];
        
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $db_driver = 'mysql';

        // Optional MySQLi backward compatibility
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
            if (!$conn->connect_error) {
                $conn->set_charset("utf8mb4");
            }
        } catch (Exception $e) {}
    } catch (Exception $e1) {
        // Attempt database creation if missing on local dev
        try {
            $dsn_no_db = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
            $temp_pdo = new PDO($dsn_no_db, $db_user, $db_pass, [PDO::ATTR_TIMEOUT => 2]);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
            $db_driver = 'mysql';
        } catch (Exception $e2) {
            $db_error_message = $e2->getMessage();
        }
    }
}

// 4. Zero-Config SQLite Auto-Fallback
if (!$pdo) {
    try {
        $data_dir = __DIR__ . '/data';
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0777, true);
        }
        $sqlite_file = is_writable($data_dir) ? $data_dir . '/ration_shop.sqlite' : sys_get_temp_dir() . '/ration_shop.sqlite';
        
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $db_driver = 'sqlite';
    } catch (Exception $e3) {
        error_log("SQLite fallback error: " . $e3->getMessage());
    }
}

// 5. Automatic Table Schema Self-Healing
if ($pdo) {
    try {
        if ($db_driver === 'pgsql') {
            // PostgreSQL Schema Auto-Init
            $check = $pdo->query("SELECT to_regclass('public.users')")->fetchColumn();
            if (!$check) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        mobile VARCHAR(15) NOT NULL,
                        email VARCHAR(120) NOT NULL UNIQUE,
                        ration_card_number VARCHAR(50) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS ration_cards (
                        id SERIAL PRIMARY KEY,
                        card_type VARCHAR(20) NOT NULL UNIQUE,
                        display_name VARCHAR(50) NOT NULL,
                        eligibility VARCHAR(255) NOT NULL,
                        rice_quota_kg NUMERIC(6,2) DEFAULT 0.00,
                        wheat_quota_kg NUMERIC(6,2) DEFAULT 0.00,
                        sugar_quota_kg NUMERIC(6,2) DEFAULT 0.00,
                        kerosene_quota_l NUMERIC(6,2) DEFAULT 0.00,
                        price_per_month NUMERIC(6,2) DEFAULT 0.00,
                        color_hex VARCHAR(10) NOT NULL
                    );

                    INSERT INTO ration_cards (card_type, display_name, eligibility, rice_quota_kg, wheat_quota_kg, sugar_quota_kg, kerosene_quota_l, price_per_month, color_hex)
                    VALUES 
                    ('yellow', 'Yellow Card (BPL / Antyodaya)', 'Below Poverty Line families (Annual income < ₹15,000)', 20.00, 15.00, 2.00, 5.00, 50.00, '#eab308'),
                    ('orange', 'Orange Card (APL)', 'Families with annual income between ₹15,000 - ₹1,00,000', 10.00, 8.00, 1.00, 2.00, 120.00, '#f97316'),
                    ('white', 'White Card (Non-Subsidized)', 'Families with annual income > ₹1,00,000', 5.00, 5.00, 1.00, 0.00, 250.00, '#64748b')
                    ON CONFLICT (card_type) DO NOTHING;

                    CREATE TABLE IF NOT EXISTS user_selections (
                        id SERIAL PRIMARY KEY,
                        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                        ration_card_type VARCHAR(20) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS bookings (
                        id SERIAL PRIMARY KEY,
                        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                        ration_card_type VARCHAR(20) NOT NULL,
                        booking_date DATE NOT NULL,
                        time_slot VARCHAR(50) NOT NULL,
                        token_number VARCHAR(30) NOT NULL UNIQUE,
                        status VARCHAR(20) DEFAULT 'confirmed',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS admins (
                        id SERIAL PRIMARY KEY,
                        username VARCHAR(50) NOT NULL UNIQUE,
                        email VARCHAR(100) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        role VARCHAR(30) DEFAULT 'admin',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    INSERT INTO admins (username, email, password, full_name, role)
                    VALUES ('admin', 'admin@rationshop.gov', '$2y$10$w8.Wq8uGkJvC0nU95l3w6euL5hGk95n7jN60N6m1pZg5qWz5rQ.lS', 'System Administrator', 'super_admin')
                    ON CONFLICT (username) DO NOTHING;
                ");
            }
        } elseif ($db_driver === 'sqlite') {
            // SQLite Schema Auto-Init
            $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
            if (!$check) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name VARCHAR(100) NOT NULL,
                        mobile VARCHAR(15) NOT NULL,
                        email VARCHAR(120) NOT NULL UNIQUE,
                        ration_card_number VARCHAR(50) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS ration_cards (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        card_type VARCHAR(20) NOT NULL UNIQUE,
                        display_name VARCHAR(50) NOT NULL,
                        eligibility VARCHAR(255) NOT NULL,
                        rice_quota_kg DECIMAL(6,2) DEFAULT 0.00,
                        wheat_quota_kg DECIMAL(6,2) DEFAULT 0.00,
                        sugar_quota_kg DECIMAL(6,2) DEFAULT 0.00,
                        kerosene_quota_l DECIMAL(6,2) DEFAULT 0.00,
                        price_per_month DECIMAL(6,2) DEFAULT 0.00,
                        color_hex VARCHAR(10) NOT NULL
                    );

                    INSERT OR IGNORE INTO ration_cards (card_type, display_name, eligibility, rice_quota_kg, wheat_quota_kg, sugar_quota_kg, kerosene_quota_l, price_per_month, color_hex)
                    VALUES 
                    ('yellow', 'Yellow Card (BPL / Antyodaya)', 'Below Poverty Line families (Annual income < ₹15,000)', 20.00, 15.00, 2.00, 5.00, 50.00, '#eab308'),
                    ('orange', 'Orange Card (APL)', 'Families with annual income between ₹15,000 - ₹1,00,000', 10.00, 8.00, 1.00, 2.00, 120.00, '#f97316'),
                    ('white', 'White Card (Non-Subsidized)', 'Families with annual income > ₹1,00,000', 5.00, 5.00, 1.00, 0.00, 250.00, '#64748b');

                    CREATE TABLE IF NOT EXISTS user_selections (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        ration_card_type VARCHAR(20) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS bookings (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        ration_card_type VARCHAR(20) NOT NULL,
                        booking_date DATE NOT NULL,
                        time_slot VARCHAR(50) NOT NULL,
                        token_number VARCHAR(30) NOT NULL UNIQUE,
                        status VARCHAR(20) DEFAULT 'confirmed',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS admins (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        username VARCHAR(50) NOT NULL UNIQUE,
                        email VARCHAR(100) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        role VARCHAR(30) DEFAULT 'admin',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    INSERT OR IGNORE INTO admins (username, email, password, full_name, role)
                    VALUES ('admin', 'admin@rationshop.gov', '$2y$10$w8.Wq8uGkJvC0nU95l3w6euL5hGk95n7jN60N6m1pZg5qWz5rQ.lS', 'System Administrator', 'super_admin');
                ");
            }
        } else {
            // MySQL Schema Auto-Init
            $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$check) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `mobile` VARCHAR(15) NOT NULL,
                        `email` VARCHAR(120) NOT NULL UNIQUE,
                        `ration_card_number` VARCHAR(50) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `ration_cards` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `card_type` VARCHAR(20) NOT NULL UNIQUE,
                        `display_name` VARCHAR(50) NOT NULL,
                        `eligibility` VARCHAR(255) NOT NULL,
                        `rice_quota_kg` DECIMAL(6,2) DEFAULT 0.00,
                        `wheat_quota_kg` DECIMAL(6,2) DEFAULT 0.00,
                        `sugar_quota_kg` DECIMAL(6,2) DEFAULT 0.00,
                        `kerosene_quota_l` DECIMAL(6,2) DEFAULT 0.00,
                        `price_per_month` DECIMAL(6,2) DEFAULT 0.00,
                        `color_hex` VARCHAR(10) NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    INSERT IGNORE INTO `ration_cards` (`card_type`, `display_name`, `eligibility`, `rice_quota_kg`, `wheat_quota_kg`, `sugar_quota_kg`, `kerosene_quota_l`, `price_per_month`, `color_hex`)
                    VALUES 
                    ('yellow', 'Yellow Card (BPL / Antyodaya)', 'Below Poverty Line families (Annual income < ₹15,000)', 20.00, 15.00, 2.00, 5.00, 50.00, '#eab308'),
                    ('orange', 'Orange Card (APL)', 'Families with annual income between ₹15,000 - ₹1,00,000', 10.00, 8.00, 1.00, 2.00, 120.00, '#f97316'),
                    ('white', 'White Card (Non-Subsidized)', 'Families with annual income > ₹1,00,000', 5.00, 5.00, 1.00, 0.00, 250.00, '#64748b');

                    CREATE TABLE IF NOT EXISTS `user_selections` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL,
                        `ration_card_type` VARCHAR(20) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `bookings` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL,
                        `ration_card_type` VARCHAR(20) NOT NULL,
                        `booking_date` DATE NOT NULL,
                        `time_slot` VARCHAR(50) NOT NULL,
                        `token_number` VARCHAR(30) NOT NULL UNIQUE,
                        `status` ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `admins` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `email` VARCHAR(100) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `full_name` VARCHAR(100) NOT NULL,
                        `role` VARCHAR(30) DEFAULT 'admin',
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    INSERT IGNORE INTO `admins` (`username`, `email`, `password`, `full_name`, `role`)
                    VALUES ('admin', 'admin@rationshop.gov', '$2y$10$w8.Wq8uGkJvC0nU95l3w6euL5hGk95n7jN60N6m1pZg5qWz5rQ.lS', 'System Administrator', 'super_admin');
                ");
            }
        }
    } catch (Exception $e) {
        error_log("Schema auto-init notice: " . $e->getMessage());
    }
}
