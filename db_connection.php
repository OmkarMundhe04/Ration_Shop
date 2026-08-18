<?php
/**
 * Online Ration Shop - Cloud-Ready Database Connection
 * 
 * Supports:
 * - Cloud database URLs (DATABASE_URL, JAWSDB_URL, CLEARDB_DATABASE_URL, MYSQL_URL)
 * - Individual environment variables (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT)
 * - Railway / Render / Fly.io / Heroku environment patterns
 * - Local development fallback (XAMPP / WAMP / Docker defaults)
 * - Auto-initialization of tables if not yet created
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Resolve Connection Parameters
$db_host = 'localhost';
$db_port = 3306;
$db_user = 'root';
$db_pass = '';
$db_name = 'ration_shop';

// Check for single Database URL (Render / Heroku / Railway / Fly.io)
$db_url = getenv('DATABASE_URL') ?: getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    $url_parts = parse_url($db_url);
    if ($url_parts) {
        $db_host = $url_parts['host'] ?? $db_host;
        $db_port = $url_parts['port'] ?? $db_port;
        $db_user = $url_parts['user'] ?? $db_user;
        $db_pass = $url_parts['pass'] ?? $db_pass;
        $db_name = ltrim($url_parts['path'] ?? $db_name, '/');
    }
} else {
    // Check for individual environment variables
    $db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: $db_host;
    $db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: $db_port;
    $db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: $db_user;
    $db_pass = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: $db_pass;
    $db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: $db_name;
}

// 2. Establish PDO Connection
$pdo = null;
$conn = null;

try {
    // Attempt connecting directly to the target database
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If database doesn't exist on local/dev, attempt to create it
    try {
        $dsn_no_db = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
        $temp_pdo = new PDO($dsn_no_db, $db_user, $db_pass);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
    } catch (PDOException $e2) {
        // Log error and show friendly message in non-production
        error_log("Database connection error: " . $e2->getMessage());
        $db_error_message = $e2->getMessage();
    }
}

// 3. Establish MySQLi Connection (for backward compatibility)
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8mb4");
    }
} catch (Exception $e) {
    // MySQLi fallback error logged silently
    error_log("MySQLi connection error: " . $e->getMessage());
}

// 4. Automatic Schema Self-Healing / Auto-Initialization
if ($pdo) {
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$table_check) {
            // Run table initialization
            $init_sql = "
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
            ";
            $pdo->exec($init_sql);
        }
    } catch (Exception $e) {
        error_log("Schema auto-init notice: " . $e->getMessage());
    }
}
