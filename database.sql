-- Tạo database
CREATE DATABASE IF NOT EXISTS webshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webshop;

-- Bảng users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `money` bigint(20) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng orders_bloxfruits
CREATE TABLE IF NOT EXISTS `orders_bloxfruits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `service` varchar(50) NOT NULL,
  `price` int(11) NOT NULL,
  `game_username` varchar(100) NOT NULL,
  `game_password` varchar(255) NOT NULL,
  `note` text,
  `status` enum('PENDING','PROCESSING','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `progress` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng history_napthe
CREATE TABLE IF NOT EXISTS `history_napthe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `telco` varchar(20) NOT NULL,
  `amount` int(11) NOT NULL,
  `pin` varchar(50) NOT NULL,
  `seri` varchar(50) NOT NULL,
  `request_id` varchar(100) NOT NULL,
  `trans_id` varchar(100) DEFAULT NULL,
  `real_amount` int(11) DEFAULT 0,
  `status` enum('PENDING','SUCCESS','FAILED','WRONG_AMOUNT','ERROR') DEFAULT 'PENDING',
  `message` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `request_id` (`request_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
