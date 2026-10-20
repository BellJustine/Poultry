-- =====================================================================
--  Poultry Haven — Migration 001: Import legacy business data
-- =====================================================================
--  PURPOSE
--    The previous version of this project stored its real records in a
--    second, older database named `poultryhub_db`, whose schema no
--    longer matches the application (`image` instead of `image_path`,
--    no `categories` table, no `category_id`, no `email` on admins).
--    This migration COPIES those records into the configured database
--    `poultry_db` and maps them onto the current schema.
--
--  SAFETY
--    * READ-ONLY on `poultryhub_db` — nothing is deleted or modified.
--    * Pure INSERT on `poultry_db` — no DROP, no TRUNCATE, no UPDATE
--      of existing rows, no table is recreated.
--    * Idempotent: rows that already exist in `poultry_db` (matched on
--      `name` / `username`) are skipped, so running it twice is safe.
--    * Existing rows in `poultry_db` are never touched.
--
--  HOW TO APPLY
--    phpMyAdmin → select database `poultry_db` → Import tab →
--    choose this file → Format: SQL → Go.
--    (Or: mysql -u root < migrations/001_import_legacy_data.sql)
-- =====================================================================

USE `poultry_db`;

-- ---------------------------------------------------------------------
-- 1. Administrators
--    The legacy `admins` table has no `email` column, but the current
--    schema requires one, so a default address is supplied. The stored
--    bcrypt hash is copied verbatim, so the existing password keeps
--    working.
-- ---------------------------------------------------------------------
--    The legacy `admins` table may or may not have an `email` column, so
--    the statement is generated dynamically based on the real schema.
SET @legacy_admin_has_email := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'poultryhub_db' AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'email'
);
SET @legacy_admin_email_col := IF(@legacy_admin_has_email > 0,
    'COALESCE(NULLIF(l.`email`, \'\'), CONCAT(l.`username`, \'@poultryhaven.local\'))',
    'CONCAT(l.`username`, \'@poultryhaven.local\')');
SET @sql := CONCAT(
    'INSERT INTO `poultry_db`.`admins` (`username`, `email`, `password_hash`) ',
    'SELECT l.`username`, ', @legacy_admin_email_col, ', l.`password_hash` ',
    'FROM `poultryhub_db`.`admins` l ',
    'LEFT JOIN `poultry_db`.`admins` t ON t.`username` = l.`username` ',
    'WHERE t.`id` IS NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2. Chickens
--    Mapping applied:
--      age_months  -> integer months
--      category_id -> derived from age
--                       4 months      -> 1  (4-Month-Old Chickens)
--                       5 months      -> 2  (5-Month-Old Chickens)
--                       6 months or + -> 3  (6-Month-Old Chickens and Older)
--      image       -> image_path, prefixed with `chickens/` because the
--                     picture files live in the `chickens/` folder
--      weight      -> varchar, shown as "1.60 kg"
--      health_notes / feeding_information / special_characteristics
--                   -> filled in with the farm's standard values
-- ---------------------------------------------------------------------
INSERT INTO `poultry_db`.`chickens`
  (`name`, `breed`, `age_months`, `category_id`, `price`, `description`,
   `quantity`, `image_path`, `gender`, `color`, `weight`,
   `health_notes`, `feeding_information`, `special_characteristics`,
   `is_featured`)
SELECT
    l.`name`,
    l.`breed`,
    CAST(l.`age_months` AS UNSIGNED),
    CASE
        WHEN CAST(l.`age_months` AS UNSIGNED) <= 4 THEN 1
        WHEN CAST(l.`age_months` AS UNSIGNED) =  5 THEN 2
        ELSE 3
    END,
    l.`price`,
    l.`description`,
    l.`quantity`,
    CONCAT('chickens/', l.`image`),
    l.`gender`,
    l.`color`,
    CONCAT(l.`weight`, ' kg'),
    'Vaccinated, healthy and regularly inspected by the farm team.',
    'Fed with quality pellets, corn and grains with clean drinking water.',
    'Free-range during the day, raised in a clean and well-ventilated coop.',
    l.`is_featured`
FROM `poultryhub_db`.`chickens` l
LEFT JOIN `poultry_db`.`chickens` t ON t.`name` = l.`name`
WHERE t.`id` IS NULL;

-- ---------------------------------------------------------------------
-- 3. Business settings
--    The legacy table uses farm_* keys; copy any of them that are not
--    already present in `poultry_db`.
-- ---------------------------------------------------------------------
INSERT INTO `poultry_db`.`settings` (`setting_key`, `setting_value`)
SELECT 'business_name', COALESCE((SELECT `setting_value` FROM `poultryhub_db`.`settings` WHERE `setting_key` = 'farm_name'), 'Poultry Haven')
WHERE NOT EXISTS (SELECT 1 FROM `poultry_db`.`settings` WHERE `setting_key` = 'business_name');

INSERT INTO `poultry_db`.`settings` (`setting_key`, `setting_value`)
SELECT 'address', COALESCE((SELECT `setting_value` FROM `poultryhub_db`.`settings` WHERE `setting_key` = 'farm_location'), 'Local Farm, Philippines')
WHERE NOT EXISTS (SELECT 1 FROM `poultry_db`.`settings` WHERE `setting_key` = 'address');

INSERT INTO `poultry_db`.`settings` (`setting_key`, `setting_value`)
SELECT 'contact_number', COALESCE((SELECT `setting_value` FROM `poultryhub_db`.`settings` WHERE `setting_key` = 'farm_phone'), '+63 900 000 0000')
WHERE NOT EXISTS (SELECT 1 FROM `poultry_db`.`settings` WHERE `setting_key` = 'contact_number');

INSERT INTO `poultry_db`.`settings` (`setting_key`, `setting_value`)
SELECT 'email', COALESCE((SELECT `setting_value` FROM `poultryhub_db`.`settings` WHERE `setting_key` = 'farm_email'), 'info@poultryhaven.local')
WHERE NOT EXISTS (SELECT 1 FROM `poultry_db`.`settings` WHERE `setting_key` = 'email');

INSERT INTO `poultry_db`.`settings` (`setting_key`, `setting_value`)
SELECT 'business_hours', COALESCE((SELECT `setting_value` FROM `poultryhub_db`.`settings` WHERE `setting_key` = 'farm_hours'), 'Mon-Sat 8:00 AM - 5:00 PM')
WHERE NOT EXISTS (SELECT 1 FROM `poultry_db`.`settings` WHERE `setting_key` = 'business_hours');

-- ---------------------------------------------------------------------
-- 4. Keep `updated_at` automatically refreshed
--    (Only applied when the column does not already auto-update.)
-- ---------------------------------------------------------------------
SET @has_auto_update := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'poultry_db' AND TABLE_NAME = 'chickens'
      AND COLUMN_NAME = 'updated_at' AND EXTRA LIKE '%on update%'
);
SET @sql := IF(@has_auto_update = 0,
    'ALTER TABLE `poultry_db`.`chickens` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
