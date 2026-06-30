-- Trakmile: Quote requests & Admin tables
-- Run on database: trak_db

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `quote_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('new','read','contacted') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username=admin  password=Trakmile@2026
INSERT IGNORE INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$10$zR0n7CoyAOd6qDs53gC/uuyOORmGuD1VIXIiX7yVa13IZCdDugCky');
