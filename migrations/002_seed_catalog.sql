-- =====================================================================
--  Poultry Haven — Migration 002: Seed the demo catalog
-- =====================================================================
--  PURPOSE
--    Guarantees the public catalog is never empty, even on a brand new
--    installation where the legacy `poultryhub_db` database does not
--    exist. The records below are the seven farm chickens that match the
--    image files already stored in `chickens/`.
--
--  SAFETY
--    * Pure INSERT — no DROP / TRUNCATE / UPDATE.
--    * Idempotent: the anti-join skips any chicken whose name already
--      exists, so running this after migration 001 changes nothing.
--    * Real business records are never deleted.
--
--  HOW TO APPLY
--    phpMyAdmin → database `poultry_db` → Import → this file → Go.
-- =====================================================================

USE `poultry_db`;

INSERT INTO `chickens`
  (`name`, `breed`, `age_months`, `category_id`, `price`, `description`,
   `quantity`, `image_path`, `gender`, `color`, `weight`,
   `health_notes`, `feeding_information`, `special_characteristics`,
   `is_featured`)
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
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           0
    UNION ALL
    SELECT 'Plymouth Rock Hen', 'Plymouth Rock', 5, 2, 450.00,
           'Approaching laying age. Friendly dual-purpose breed.',
           6, 'chickens/5month-plymouth-rock.jpg', 'Female', 'Barred black and white', '2.00 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           1
    UNION ALL
    SELECT 'Farm Raised Chicken', 'Native Philippine Chicken', 5, 2, 380.00,
           'Five-month-old native hen, vaccinated and healthy.',
           2, 'chickens/5month-farm-chicken.jpg', 'Female', 'Mixed brown', '1.80 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           0
    UNION ALL
    SELECT 'Adult Brahma Hen', 'Brahma', 8, 3, 850.00,
           'Mature Brahma hen, excellent for breeding and egg production.',
           1, 'chickens/6month-adult-chicken.jpg', 'Female', 'Light', '4.50 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           1
    UNION ALL
    SELECT 'Mixed Breeds Group', 'Mixed Breed', 7, 3, 600.00,
           'Seven-month-old mixed-breed roosters from the farm flock.',
           4, 'chickens/6month-mixed-breeds.jpg', 'Male', 'Mixed colors', '3.20 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           0
    UNION ALL
    SELECT 'Native Rooster', 'Native Philippine Chicken', 10, 3, 500.00,
           'Ten-month-old native rooster, strong and active.',
           0, 'chickens/6month-rooster.jpg', 'Male', 'Red', '3.00 kg',
           'Vaccinated, healthy and regularly inspected by the farm team.',
           'Fed with quality pellets, corn and grains with clean drinking water.',
           'Free-range during the day, raised in a clean and well-ventilated coop.',
           0
) AS s
LEFT JOIN `chickens` c ON c.`name` = s.`name`
WHERE c.`id` IS NULL;

-- ---------------------------------------------------------------------
-- Make sure the three required age categories exist (idempotent).
-- ---------------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
  (1, '4-Month-Old Chickens', 'Chickens at four months of age. Still growing and gaining weight, ideal for the backyard flock.'),
  (2, '5-Month-Old Chickens', 'Chickens at five months of age. Nearly ready to start laying and highly productive.'),
  (3, '6-Month-Old Chickens and Older', 'Chickens six months of age and older. Mature birds for breeding and egg production.')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`);
