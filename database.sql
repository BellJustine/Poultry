-- =====================================================================
--  Poultry Haven — database.sql
--  Schema + starter data for a fresh installation.
--
--  HOW TO USE (phpMyAdmin)
--    1. Open http://localhost/phpmyadmin
--    2. Click the "Import" tab
--    3. Choose this file, set Format to "SQL", press "Go"
--
--  This script is IDEMPOTENT — running it again will NOT delete or
--  duplicate existing rows, and will never drop a table.
--
--  Default administrator
--    username: admin
--    password: admin123      <- change it from Admin → Settings
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `poultry_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `poultry_db`;

-- ---------------------------------------------------------------------
-- Table: admins
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `email`         VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: categories (the three required age groups)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: chickens (the public catalog + inventory)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chickens` (
  `id`                     INT AUTO_INCREMENT PRIMARY KEY,
  `name`                   VARCHAR(100) NOT NULL,
  `breed`                  VARCHAR(100) NOT NULL,
  `age_months`             INT NOT NULL,
  `category_id`            INT NOT NULL,
  `price`                  DECIMAL(10,2) NOT NULL,
  `description`            TEXT DEFAULT NULL,
  `quantity`               INT NOT NULL DEFAULT 0,
  `image_path`             VARCHAR(255) DEFAULT NULL,
  `gender`                 VARCHAR(20)  DEFAULT NULL,
  `color`                  VARCHAR(50)  DEFAULT NULL,
  `weight`                 VARCHAR(50)  DEFAULT NULL,
  `health_notes`           TEXT DEFAULT NULL,
  `feeding_information`    TEXT DEFAULT NULL,
  `special_characteristics` TEXT DEFAULT NULL,
  `is_featured`            TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_chickens_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_chickens_category` (`category_id`),
  INDEX `idx_chickens_age` (`age_months`),
  INDEX `idx_chickens_breed` (`breed`),
  INDEX `idx_chickens_quantity` (`quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: settings (business information shown on the public site)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: contact_messages (messages from the public Contact Us form)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(50)  DEFAULT NULL,
  `subject`    VARCHAR(150) DEFAULT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_messages_read` (`is_read`),
  INDEX `idx_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  Starter data (safe to run repeatedly)
-- =====================================================================

-- The three required age categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
  (1, '4-Month-Old Chickens', 'Chickens at four months of age. Still growing and gaining weight, ideal for the backyard flock.'),
  (2, '5-Month-Old Chickens', 'Chickens at five months of age. Nearly ready to start laying and highly productive.'),
  (3, '6-Month-Old Chickens and Older', 'Chickens six months of age and older. Mature birds for breeding and egg production.')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`);

-- Business information
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('business_name', 'Poultry Haven'),
  ('business_description', 'Farm-Fresh Quality, Raised with Care.'),
  ('contact_number', '+63 900 000 0000'),
  ('email', 'info@poultryhaven.local'),
  ('address', 'Local Farm, Philippines'),
  ('business_hours', 'Mon-Sat 8:00 AM - 5:00 PM')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`);

-- Default administrator (password: admin123) — skipped if it already exists
INSERT INTO `admins` (`username`, `email`, `password_hash`)
SELECT 'admin', 'admin@poultryhaven.local', '$2y$10$x2BOXAlzQOM18Pwm6clVAef0lCH3HcrCkGJq2xr22bLWKGS.GnaBC'
WHERE NOT EXISTS (SELECT 1 FROM `admins` WHERE `username` = 'admin');

-- Demo catalog matching the images in the `chickens/` folder
INSERT INTO `chickens`
  (`name`, `breed`, `age_months`, `category_id`, `price`, `description`,
   `quantity`, `image_path`, `gender`, `color`, `weight`,
   `health_notes`, `feeding_information`, `special_characteristics`, `is_featured`)
SELECT s.`name`, s.`breed`, s.`age_months`, s.`category_id`, s.`price`,
       s.`description`, s.`quantity`, s.`image_path`, s.`gender`, s.`color`,
       s.`weight`, s.`health_notes`, s.`feeding_information`,
       s.`special_characteristics`, s.`is_featured`
FROM (
    SELECT 'Rhode Island Red Pullet' AS `name`, 'Rhode Island Red' AS `breed`,
           4 AS `age_months`, 1 AS `category_id`, 350.00 AS `price`,
           'Healthy 4-month-old pullet raised on the farm. Great for backyard flocks.' AS `description`,
           8 AS `quantity`, 'chickens/4month-rhode-island-red.jpg' AS `image_path`,
           'Female' AS `gender`, 'Reddish-brown' AS `color`, '1.60 kg' AS `weight`,
           'Vaccinated, healthy and regularly inspected by the farm team.' AS `health_notes`,
           'Fed with quality pellets, corn and grains with clean drinking water.' AS `feeding_information`,
           'Free-range during the day, raised in a clean and well-ventilated coop.' AS `special_characteristics`,
           1 AS `is_featured`
    UNION ALL
    SELECT 'Brown Farm Chicken', 'Native Philippine Chicken', 4, 1, 300.00,
           'Hardy native chicken, 4 months old, well adapted to local conditions.',
           3, 'chickens/4month-brown-chicken.jpg', 'Female', 'Brown', '1.40 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 0
    UNION ALL
    SELECT 'Plymouth Rock Hen', 'Plymouth Rock', 5, 2, 450.00,
           'Approaching laying age. Friendly dual-purpose breed.',
           6, 'chickens/5month-plymouth-rock.jpg', 'Female', 'Barred black and white', '2.00 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 1
    UNION ALL
    SELECT 'Farm Raised Chicken', 'Native Philippine Chicken', 5, 2, 380.00,
           'Five-month-old native hen, vaccinated and healthy.',
           2, 'chickens/5month-farm-chicken.jpg', 'Female', 'Mixed brown', '1.80 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 0
    UNION ALL
    SELECT 'Adult Brahma Hen', 'Brahma', 8, 3, 850.00,
           'Mature Brahma hen, excellent for breeding and egg production.',
           1, 'chickens/6month-adult-chicken.jpg', 'Female', 'Light', '4.50 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 1
    UNION ALL
    SELECT 'Mixed Breeds Group', 'Mixed Breed', 7, 3, 600.00,
           'Seven-month-old mixed-breed roosters from the farm flock.',
           4, 'chickens/6month-mixed-breeds.jpg', 'Male', 'Mixed colors', '3.20 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 0
    UNION ALL
    SELECT 'Native Rooster', 'Native Philippine Chicken', 10, 3, 500.00,
           'Ten-month-old native rooster, strong and active.',
           0, 'chickens/6month-rooster.jpg', 'Male', 'Red', '3.00 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.', 0
) AS s
LEFT JOIN `chickens` c ON c.`name` = s.`name`
WHERE c.`id` IS NULL;

-- =====================================================================
--  Done. Next steps:
--    1. Open http://localhost/Poultry/          (public catalog)
--    2. Open http://localhost/Poultry/admin/login.php   (admin / admin123)
-- =====================================================================
