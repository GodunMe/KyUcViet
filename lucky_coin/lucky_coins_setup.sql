-- =====================================================
-- Lucky Coins Feature - Database Setup
-- =====================================================
-- Description: Creates tables for lucky coin spawning and pickup tracking
-- Date: 2025-10-26
-- 
-- Tables:
--   1. lucky_coins: Stores spawned coins with expiration
--   2. coin_pickups: Stores user pickup attempts with photo evidence
-- =====================================================

-- Drop existing tables if they exist (use with caution!)
-- Uncomment these lines if you want to recreate tables:
-- DROP TABLE IF EXISTS `coin_pickups`;
-- DROP TABLE IF EXISTS `lucky_coins`;

-- =====================================================
-- Table: lucky_coins
-- Purpose: Store information about spawned lucky coins
-- =====================================================
CREATE TABLE IF NOT EXISTS `lucky_coins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `museum_id` INT(11) NOT NULL,
  `spawn_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm spawn xu, coin sẽ tồn tại 10 phút',
  PRIMARY KEY (`id`),
  INDEX `idx_museum_spawn` (`museum_id`, `spawn_time`),
  INDEX `idx_spawn_time` (`spawn_time`),
  CONSTRAINT `fk_lucky_coins_museum` FOREIGN KEY (`museum_id`) 
    REFERENCES `museum` (`MuseumID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores lucky coins spawned randomly at museums - each coin lasts 10 minutes, unlimited pickups';

-- =====================================================
-- Table: coin_pickups
-- Purpose: Store user pickup attempts with photo evidence
-- =====================================================
CREATE TABLE IF NOT EXISTS `coin_pickups` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `coin_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `photo_path` VARCHAR(255) NOT NULL COMMENT 'Path to uploaded photo proof',
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `points_awarded` INT(11) NOT NULL DEFAULT 100 COMMENT 'Points awarded when approved',
  `reject_reason` TEXT DEFAULT NULL COMMENT 'Admin reason for rejection (optional)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_coin_id` (`coin_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_coin_pickups_coin` FOREIGN KEY (`coin_id`) 
    REFERENCES `lucky_coins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_coin_pickups_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores user lucky coin pickups with photo evidence - independent from checkins';

-- =====================================================
-- Sample Data (Optional - for testing)
-- =====================================================
-- Uncomment to insert test data:
/*
-- Insert a test lucky coin (just spawned - valid for 10 minutes)
INSERT INTO `lucky_coins` (`museum_id`, `spawn_time`) 
VALUES (1, NOW());

-- Insert another coin for museum 2
INSERT INTO `lucky_coins` (`museum_id`, `spawn_time`) 
VALUES (2, NOW());

-- Insert an expired coin (spawned 15 minutes ago - for testing)
INSERT INTO `lucky_coins` (`museum_id`, `spawn_time`) 
VALUES (3, DATE_SUB(NOW(), INTERVAL 15 MINUTE));
*/

-- =====================================================
-- Verification Queries
-- =====================================================
-- Run these queries after setup to verify tables were created:

-- Check lucky_coins table structure
SHOW CREATE TABLE `lucky_coins`;

-- Check coin_pickups table structure
SHOW CREATE TABLE `coin_pickups`;

-- Count records
SELECT COUNT(*) as total_coins FROM `lucky_coins`;
SELECT COUNT(*) as total_pickups FROM `coin_pickups`;

-- =====================================================
-- Cleanup/Rollback Script
-- =====================================================
-- To remove these tables completely, run:
/*
DROP TABLE IF EXISTS `coin_pickups`;
DROP TABLE IF EXISTS `lucky_coins`;
*/
