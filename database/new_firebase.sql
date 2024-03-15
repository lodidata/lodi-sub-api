ALTER TABLE `lodi_game`.`user_firebase`
DROP COLUMN `client_id`,
DROP COLUMN `session_id`,
ADD COLUMN `fire_user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `api_secret`,
ADD COLUMN `app_instance_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `fire_user_id`;