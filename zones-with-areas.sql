-- ---------------------------------------------------------------------------
-- Nazia Botanics — delivery priced by zone instead of by state
--
-- RUN THIS BEFORE DEPLOYING THE CODE. It only renames and adds columns, which
-- the currently deployed code ignores, so it is safe to apply first. Doing it
-- the other way round takes checkout down.
--
-- phpMyAdmin -> pick your database -> SQL tab -> paste -> Go.
-- Back up first (Export -> Go).
-- ---------------------------------------------------------------------------

-- 1. delivery_zones.state becomes .name, and gains areas + ordering ----------
-- CHANGE rather than RENAME COLUMN, which older MySQL and MariaDB lack.
ALTER TABLE `delivery_zones`
  CHANGE COLUMN `state` `name` varchar(255) NOT NULL;

ALTER TABLE `delivery_zones`
  ADD COLUMN `areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL AFTER `name`,
  ADD COLUMN `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0 AFTER `is_active`;

-- 2. The order records which zone was chosen --------------------------------
ALTER TABLE `orders`
  CHANGE COLUMN `delivery_state` `delivery_zone` varchar(255) DEFAULT NULL;

-- 3. Lagos zones, grouped the way delivery is actually priced ---------------
-- A state is too coarse to charge by: Lekki and Ikorodu are both Lagos and are
-- not the same trip. The areas are real; the prices are placeholders. Nothing
-- is switched on — an active zone is a price the checkout will charge, and
-- nobody has chosen these yet. Set them under Delivery in the dashboard.
INSERT INTO `delivery_zones` (`name`, `areas`, `fee`, `delivery_period`, `is_active`, `sort_order`, `created_at`, `updated_at`)
VALUES
  ('Mainland 1', '["Ogudu","Gbagada","Surulere","Ojota","Magodo","Maryland","Ikeja","Ketu","Yaba"]', 3500, '1-2 working days after dispatch', 0, 10, NOW(), NOW()),
  ('Mainland 2', '["Iyana Ipaja","Egbeda","Agege","Ago Palace","Mile 2","Festac","Satellite Town","Ijegun","Iju Ishaga","Abule Egba"]', 4000, '1-2 working days after dispatch', 0, 20, NOW(), NOW()),
  ('Mainland 3', '["Ikorodu","Ikotun","Ipaja","Idimu","Ijegun"]', 5000, '1-2 working days after dispatch', 0, 30, NOW(), NOW()),
  ('Mainland 4', '["Apapa","Ajegunle","Ijora","Costain","Alaba","Suru"]', 4000, '1-2 working days after dispatch', 0, 40, NOW(), NOW()),
  ('Island 1', '["Lekki Phase 1","Victoria Island","Ikoyi","Banana Island","Ikate"]', 4500, '1-2 working days after dispatch', 0, 50, NOW(), NOW()),
  ('Island 2', '["VGC","Agungi","Osapa London","Chevron"]', 5000, '1-2 working days after dispatch', 0, 60, NOW(), NOW()),
  ('Island 3', '["Ajah","Sangotedo","Abijo"]', 5500, '1-2 working days after dispatch', 0, 70, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = `name`;

-- 4. Tell Laravel this migration has already run -----------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_21_000004_zones_with_areas',
       (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` x)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` y WHERE y.`migration` = '2026_09_21_000004_zones_with_areas'
);

-- Done. Check:  SELECT name, fee, is_active FROM delivery_zones ORDER BY sort_order;
