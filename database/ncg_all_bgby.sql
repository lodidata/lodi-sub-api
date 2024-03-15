
ALTER TABLE `user_dml` ADD COLUMN `BG` int(10) UNSIGNED NULL DEFAULT 0,ADD COLUMN `BGBY` int(10) UNSIGNED NULL DEFAULT 0;

INSERT INTO `game_3th`(`kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `game_img`) VALUES
('105', 139, 'Fishing Master', '捕鱼大师', 'Fishing', 'FishingMaster','https://img.caacaya.com/lodi/game/vert/bg/FishingMaster.png'),
('411', 139, 'Westward Journey Fishing', '西游捕鱼', 'Fishing', 'WestwardJourneyFishing','https://img.caacaya.com/lodi/game/vert/bg/WestwardJourneyFishing.png'),
('484', 139, 'Tai Sin Fishing', '大仙捕鱼', 'Fishing', 'TaiSinFishing', 'https://img.caacaya.com/lodi/game/vert/bg/TaiSinFishing.png');

INSERT INTO `game_menu`(`id`, `pid`, `type`, `name`, `alias`, `rename`, `img`,`sort`, `status`, `switch`) VALUES (139, 22, 'BGBY', 'BGFH', 'BG', 'BG捕鱼', 'https://img.caacaya.com/lodi/menu/bg.png',  15, 'disabled','enabled');
ALTER TABLE `game_order_bg` ADD COLUMN `gameCategory` varchar(32)  NOT NULL DEFAULT 'LIVE' COMMENT '游戏类型' AFTER `payment`;