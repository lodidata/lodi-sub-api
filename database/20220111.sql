#添加用户打码游戏类型
ALTER TABLE `user_dml` 
ADD COLUMN `PG` int(10) UNSIGNED NULL DEFAULT 0 AFTER `CQ9BY`,
ADD COLUMN `KMQM` int(10) UNSIGNED NULL DEFAULT 0 AFTER `PG`,
ADD COLUMN `TF` int(10) UNSIGNED NULL DEFAULT 0 AFTER `KMQM`,
ADD COLUMN `SV388` int(10) UNSIGNED NULL DEFAULT 0 AFTER `TF`,
ADD COLUMN `RCB` int(10) UNSIGNED NULL DEFAULT 0 AFTER `SV388`;

#更新游戏名称
UPDATE `game_menu` SET `name` = 'LOTTO' WHERE `id` = 23;
UPDATE `game_menu` SET `name` = 'FISH' WHERE `id` = 22;
UPDATE `game_menu` SET `name` = 'VIRTUAL' WHERE `id` = 80;
UPDATE `game_menu` SET `name` = 'P2P' WHERE `id` = 17;
UPDATE `game_menu` SET `name` = 'JOKERSLOT' WHERE `id` = 59;
UPDATE `game_menu` SET `name` = 'JOKERFH' WHERE `id` = 60;
UPDATE `game_menu` SET `name` = 'LIVE' WHERE `id` = 15;
UPDATE `game_menu` SET `name` = 'JILISLOT' WHERE `id` = 62;
UPDATE `game_menu` SET `name` = 'JILIFH' WHERE `id` = 63;
UPDATE `game_menu` SET `name` = 'PPSLOT' WHERE `id` = 64;
UPDATE `game_menu` SET `name` = 'PPFH' WHERE `id` = 65;
UPDATE `game_menu` SET `name` = 'JOKERLV' WHERE `id` = 61;
UPDATE `game_menu` SET `name` = 'PPLV' WHERE `id` = 66;
UPDATE `game_menu` SET `name` = 'EVOLV' WHERE `id` = 67;
UPDATE `game_menu` SET `name` = 'PNGSLOT' WHERE `id` = 68;
UPDATE `game_menu` SET `name` = 'JDBSLOT' WHERE `id` = 70;
UPDATE `game_menu` SET `name` = 'JDBFH' WHERE `id` = 71;
UPDATE `game_menu` SET `name` = 'SBOSPORT' WHERE `id` = 72;
UPDATE `game_menu` SET `name` = 'SALV' WHERE `id` = 73;
UPDATE `game_menu` SET `name` = 'CQ9SLOT' WHERE `id` = 74;
UPDATE `game_menu` SET `name` = 'CQ9FH' WHERE `id` = 75;
UPDATE `game_menu` SET `name` = 'PGSLOT' WHERE `id` = 76;
UPDATE `game_menu` SET `name` = 'COOKFIGHT' WHERE `id` = 79;
UPDATE `game_menu` SET `name` = 'LOTTOSTA' WHERE `id` = 26;
UPDATE `game_menu` SET `name` = 'LOTTOCHAT' WHERE `id` = 27;

#新建收藏表
CREATE TABLE `dev_game`.`favorites`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL COMMENT '用户id',
  `game_id` int UNSIGNED NOT NULL COMMENT '游戏id',
  PRIMARY KEY (`id`)
) COMMENT = '收藏表';

#唯一索引
ALTER TABLE `dev_game`.`favorites`
ADD UNIQUE INDEX `udx_userid_gameid`(`user_id`, `game_id`) USING BTREE;