ALTER TABLE `game_menu`
MODIFY COLUMN `img` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '竖版图片' AFTER `qp_img2`;
ALTER TABLE `game_3th`
MODIFY COLUMN `game_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '竖版图片' AFTER `qp_img`;
ALTER TABLE `game_menu`
MODIFY COLUMN `qp_img` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '棋牌图片,横版图片' AFTER `rename`;
ALTER TABLE `game_3th`
MODIFY COLUMN `qp_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '棋牌图片,横版图片' AFTER `alias`;


#新游戏分类返佣 redis del system.config.global.key
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `state`) 
VALUES ('rakeBack', '电竞返佣', 'int', 'ESPORTS', '10','enabled'),
('rakeBack', '街机返佣', 'int', 'ARCADE', '10','enabled'),
('rakeBack', '桌面游戏返佣', 'int', 'TABLE', '10','enabled'),
('audit', '电竞稽核', 'int', 'ESPORTS', '100', 'enabled'),
('audit', '街机稽核', 'int', 'ARCADE', '100', 'enabled'),
('audit', '桌面游戏稽核', 'int', 'TABLE', '100', 'enabled');


#返佣规则游戏字段太小
ALTER TABLE  `active_bkge` 
MODIFY COLUMN `game_ids` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
COMMENT '返佣类目，游戏大类ID' AFTER `etime`;

UPDATE `active_bkge` SET `etime`='2023-03-01 00:00:00', `game_ids` = '4,15,17,22,16,18,19,20' WHERE `id`=4;