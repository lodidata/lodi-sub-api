ALTER TABLE `game_api` 
MODIFY COLUMN `lobby` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '特殊参数' AFTER `name`;

UPDATE `game_api` SET `lobby` = '{\"LIVE\":{\"limitId\":[150901]}}' WHERE `id` = 69;