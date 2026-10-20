-- =====================================================================
--  Poultry Haven — Migration 003: Contact messages table
-- =====================================================================
--  PURPOSE
--    Adds the table that stores messages submitted through the public
--    Contact Us form so the administrator can read them in
--    admin/messages.php.
--
--  SAFETY
--    * Additive only — creates one new table, touches nothing else.
--    * `CREATE TABLE IF NOT EXISTS` makes it safe to run repeatedly.
--
--  HOW TO APPLY
--    phpMyAdmin → database `poultry_db` → Import → this file → Go.
-- =====================================================================

USE `poultry_db`;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(50)  DEFAULT NULL,
  `subject`    VARCHAR(150) DEFAULT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_read` (`is_read`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
