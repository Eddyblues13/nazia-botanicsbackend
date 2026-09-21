-- ---------------------------------------------------------------------------
-- Nazia Botanics — customer accounts
--
-- Run once on the LIVE database: phpMyAdmin -> pick your database -> SQL tab.
-- Safe to run twice: every step checks for itself first.
-- Back up first (Export -> Go).
-- ---------------------------------------------------------------------------

-- 1. Shopper accounts ---------------------------------------------------------
-- Usually already present from Laravel's own migrations; created here only if
-- this deployment never had it.
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Link an order to the account that placed it ------------------------------
-- Nullable, because checkout stays open to guests: an account is a
-- convenience, not a condition of buying. Existing orders keep NULL, which is
-- correct — they were placed without one.
DROP PROCEDURE IF EXISTS `nb_add_user_to_orders`;
DELIMITER $$
CREATE PROCEDURE `nb_add_user_to_orders`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'user_id'
  ) THEN
    ALTER TABLE `orders`
      ADD COLUMN `user_id` bigint(20) unsigned DEFAULT NULL AFTER `id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
      AND INDEX_NAME = 'orders_user_id_foreign'
  ) THEN
    ALTER TABLE `orders` ADD KEY `orders_user_id_foreign` (`user_id`);
  END IF;

  -- ON DELETE SET NULL: deleting an account must not delete the orders behind
  -- it, which are the shop's sales records.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
      AND CONSTRAINT_NAME = 'orders_user_id_foreign'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `orders`
      ADD CONSTRAINT `orders_user_id_foreign`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
  END IF;
END$$
DELIMITER ;

CALL nb_add_user_to_orders();
DROP PROCEDURE IF EXISTS `nb_add_user_to_orders`;

-- 3. Tell Laravel this migration has already run ------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m, b FROM (
  SELECT '2026_09_21_000003_add_user_to_orders' AS m,
         (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` x) AS b
) AS to_add
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` y WHERE y.`migration` = to_add.m
);

-- Done. Check:  SHOW COLUMNS FROM orders LIKE 'user_id';   -- expect one row
