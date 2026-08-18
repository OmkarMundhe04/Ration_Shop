-- ==========================================================
-- Online Ration Shop - Database Schema & Initial Data
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- ==========================================================

-- Create Database if not exists (for local environments)
CREATE DATABASE IF NOT EXISTS `ration_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ration_shop`;

-- ----------------------------------------------------------
-- 1. Users Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `ration_card_number` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_card` (`ration_card_number`),
    INDEX `idx_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Ration Card Types & Quota Reference Table
-- ----------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Card Types
INSERT INTO `ration_cards` (`card_type`, `display_name`, `eligibility`, `rice_quota_kg`, `wheat_quota_kg`, `sugar_quota_kg`, `kerosene_quota_l`, `price_per_month`, `color_hex`)
VALUES 
('yellow', 'Yellow Card (BPL / Antyodaya)', 'Below Poverty Line families with annual income < ₹15,000', 20.00, 15.00, 2.00, 5.00, 50.00, '#eab308'),
('orange', 'Orange Card (APL)', 'Families with annual income between ₹15,000 and ₹1,00,000', 10.00, 8.00, 1.00, 2.00, 120.00, '#f97316'),
('white', 'White Card (Non-Subsidized)', 'Families with annual income > ₹1,00,000', 5.00, 5.00, 1.00, 0.00, 250.00, '#64748b')
ON DUPLICATE KEY UPDATE 
    `display_name` = VALUES(`display_name`),
    `eligibility` = VALUES(`eligibility`);

-- ----------------------------------------------------------
-- 3. User Ration Card Selections Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_selections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ration_card_type` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Bookings Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ration_card_type` VARCHAR(20) NOT NULL,
    `booking_date` DATE NOT NULL,
    `time_slot` VARCHAR(50) NOT NULL,
    `token_number` VARCHAR(30) NOT NULL UNIQUE,
    `status` ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_booking_date` (`booking_date`),
    INDEX `idx_user_booking` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. Admin Users Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(30) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin Account (Username: admin, Password: admin123)
-- Password hash generated using password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`)
VALUES 
('admin', 'admin@rationshop.gov', '$2y$10$w8.Wq8uGkJvC0nU95l3w6euL5hGk95n7jN60N6m1pZg5qWz5rQ.lS', 'System Administrator', 'super_admin')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);
