-- ---------------------------------------------------------------------------
-- Nazia Botanics — Paystack payments + delivery zones
--
-- Run once on the LIVE database: phpMyAdmin -> your database -> SQL tab ->
-- paste -> Go.
--
-- Safe to run twice: every step checks for itself first, so a second run
-- changes nothing. Back up first anyway (phpMyAdmin -> Export -> Go).
-- ---------------------------------------------------------------------------

-- 1. Delivery zones -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `delivery_zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `fee` int(10) unsigned NOT NULL,
  `delivery_period` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_zones_state_unique` (`state`),
  KEY `delivery_zones_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Payment + delivery columns on orders -------------------------------------
-- MySQL has no "ADD COLUMN IF NOT EXISTS", so each column is added only when
-- missing. That is what makes this safe to re-run.
DROP PROCEDURE IF EXISTS `nb_add_column`;
DELIMITER $$
CREATE PROCEDURE `nb_add_column`(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL nb_add_column('orders', 'delivery_state',    "`delivery_state` varchar(255) DEFAULT NULL AFTER `delivery_address`");
CALL nb_add_column('orders', 'delivery_fee',      "`delivery_fee` int(10) unsigned NOT NULL DEFAULT 0 AFTER `delivery_state`");
CALL nb_add_column('orders', 'delivery_period',   "`delivery_period` varchar(255) DEFAULT NULL AFTER `delivery_fee`");
CALL nb_add_column('orders', 'total',             "`total` int(10) unsigned NOT NULL DEFAULT 0 AFTER `subtotal`");
CALL nb_add_column('orders', 'payment_status',    "`payment_status` varchar(255) NOT NULL DEFAULT 'unpaid' AFTER `total`");
CALL nb_add_column('orders', 'payment_reference', "`payment_reference` varchar(255) DEFAULT NULL AFTER `payment_status`");
CALL nb_add_column('orders', 'payment_channel',   "`payment_channel` varchar(255) DEFAULT NULL AFTER `payment_reference`");
CALL nb_add_column('orders', 'amount_paid',       "`amount_paid` int(10) unsigned NOT NULL DEFAULT 0 AFTER `payment_channel`");
CALL nb_add_column('orders', 'paid_at',           "`paid_at` timestamp NULL DEFAULT NULL AFTER `amount_paid`");
DROP PROCEDURE IF EXISTS `nb_add_column`;

DROP PROCEDURE IF EXISTS `nb_add_index`;
DELIMITER $$
CREATE PROCEDURE `nb_add_index`(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD ', ddl);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL nb_add_index('orders', 'orders_payment_reference_unique', "UNIQUE KEY `orders_payment_reference_unique` (`payment_reference`)");
CALL nb_add_index('orders', 'orders_payment_status_index',     "KEY `orders_payment_status_index` (`payment_status`)");
DROP PROCEDURE IF EXISTS `nb_add_index`;

-- 3. Orders placed before this change -----------------------------------------
-- They were settled off-site and never carried a delivery charge, so their
-- total is their subtotal. Marking them paid keeps them counting as revenue
-- and stops the site showing an old customer their order as unpaid.
UPDATE `orders` SET `total` = `subtotal`
 WHERE `total` = 0 AND `subtotal` > 0;

UPDATE `orders`
   SET `payment_status` = 'paid', `amount_paid` = `subtotal`, `paid_at` = `created_at`
 WHERE `payment_status` = 'unpaid' AND `payment_reference` IS NULL;

-- 4. The states ---------------------------------------------------------------
-- Placeholder prices. Only Lagos is on: a state stays off until someone has
-- decided what delivery there costs, because an active zone is a price the
-- checkout will charge. Edit these under Delivery in the dashboard, not here.
-- Re-running leaves your edited prices untouched.
INSERT INTO `delivery_zones` (`state`, `fee`, `delivery_period`, `is_active`, `created_at`, `updated_at`)
VALUES
  ('Abia', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Adamawa', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Akwa Ibom', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Anambra', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Bauchi', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Bayelsa', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Benue', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Borno', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Cross River', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Delta', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Ebonyi', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Edo', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Ekiti', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Enugu', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('FCT - Abuja', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Gombe', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Imo', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Jigawa', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Kaduna', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Kano', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Katsina', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Kebbi', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Kogi', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Kwara', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Lagos', 3000, '1-2 business days', 1, NOW(), NOW()),
  ('Nasarawa', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Niger', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Ogun', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Ondo', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Osun', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Oyo', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Plateau', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Rivers', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Sokoto', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Taraba', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Yobe', 5000, '3-5 business days', 0, NOW(), NOW()),
  ('Zamfara', 5000, '3-5 business days', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `state` = `state`;

-- 5. Tell Laravel these migrations have already run ----------------------------
-- Without this, a later `php artisan migrate` would try to create the same
-- table and fail.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m, b FROM (
  SELECT '2026_09_21_000001_create_delivery_zones_table' AS m,
         (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` x) AS b
  UNION ALL
  SELECT '2026_09_21_000002_add_payment_to_orders',
         (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` x)
) AS to_add
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` y WHERE y.`migration` = to_add.m
);

-- Done. Check:  SELECT COUNT(*) FROM delivery_zones;   -- expect 37
