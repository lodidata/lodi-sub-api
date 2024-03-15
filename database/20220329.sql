ALTER TABLE `bank`
MODIFY COLUMN `code` char(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '银行英文简称\r\n@a:r' AFTER `id`;
MODIFY COLUMN `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '银行名称\r\n@a:r' AFTER `status`;
MODIFY COLUMN `shortname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行简称\r\n' AFTER `name`;

#AVIA配置
INSERT INTO `game_api` (`id`, `type`, `name`, `lobby`, `cagent`, `des_key`, `key`, `pub_key`, `loginUrl`, `orderUrl`, `apiUrl`, `update_at`) VALUES (84, 'AVIA', '泛亚电竞', '', NULL, NULL, '99ec43516e2e4b028aac145c77c36d17', NULL, '', 'https://api.aviaapi.vip', 'https://api.aviaapi.vip', NULL);

#AVIA菜单
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (96, 18, 'AVIA', 'AVIAGAMING', 'AVIA', '泛亚电竞', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'enabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');

#AVIA打码量
ALTER TABLE `user_dml` 
ADD COLUMN `AVIA` int(10) UNSIGNED NULL DEFAULT 0 AFTER `UG`;

#AVIA游戏
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES 
(5036, '1', 96, 'PUBG', '绝地求生', 'ESPORTS', 'PUBG', NULL, NULL, 1, '2022-03-29 16:14:42', '2022-03-29 16:14:42', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5037, '2', 96, 'LOL', '英雄联盟', 'ESPORTS', 'LOL', NULL, NULL, 2, '2022-03-29 16:14:43', '2022-03-29 16:14:43', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5039, '29', 96, 'WR', '激斗峡谷', 'ESPORTS', 'WR', NULL, NULL, 4, '2022-03-29 16:14:45', '2022-03-29 16:14:45', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5040, '10', 96, 'Dota 2', '刀塔II', 'ESPORTS', 'Dota2', NULL, NULL, 5, '2022-03-29 16:14:46', '2022-03-29 16:14:46', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5041, '2025', 96, 'CS', '反恐精英', 'ESPORTS', 'CS', NULL, NULL, 6, '2022-03-29 16:14:47', '2022-03-29 16:14:47', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5042, '2027', 96, 'OW', '守望先锋', 'ESPORTS', 'OW', NULL, NULL, 7, '2022-03-29 16:14:48', '2022-03-29 16:14:48', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5043, '2040', 96, 'KOG', '王者荣耀', 'ESPORTS', 'KOG', NULL, NULL, 8, '2022-03-29 16:14:49', '2022-03-29 16:14:49', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5044, '2083', 96, 'Warcraft III', '魔兽争霸', 'ESPORTS', 'WarcraftIII', NULL, NULL, 9, '2022-03-29 16:14:50', '2022-03-29 16:14:50', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5045, '2084', 96, 'Hearthstone', '炉石传说', 'ESPORTS', 'Hearthstone', NULL, NULL, 10, '2022-03-29 16:14:52', '2022-03-29 16:14:52', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5046, '2085', 96, 'StarCraft II', '星际争霸', 'ESPORTS', 'StarCraftII', NULL, NULL, 11, '2022-03-29 16:14:53', '2022-03-29 16:14:53', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5048, '2095', 96, 'Heroes', '风暴英雄', 'ESPORTS', 'Heroes', NULL, NULL, 13, '2022-03-29 16:14:55', '2022-03-29 16:14:55', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5049, '2106', 96, 'COD', '使命召唤', 'ESPORTS', 'COD', NULL, NULL, 14, '2022-03-29 16:14:56', '2022-03-29 16:14:56', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5050, '2321', 96, 'NBA', 'NBA2K', 'ESPORTS', 'NBA', NULL, NULL, 15, '2022-03-29 16:14:57', '2022-03-29 16:14:57', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5051, '2322', 96, 'Rainbow Six', '彩虹6号', 'ESPORTS', 'RainbowSix', NULL, NULL, 16, '2022-03-29 16:14:58', '2022-03-29 16:14:58', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5052, '2323', 96, 'SFV', '街头霸王5', 'ESPORTS', 'SFV', NULL, NULL, 17, '2022-03-29 16:14:59', '2022-03-29 16:14:59', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5053, '2324', 96, 'WOT', '坦克世界', 'ESPORTS', 'WOT', NULL, NULL, 18, '2022-03-29 16:15:00', '2022-03-29 16:15:00', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5054, '2333', 96, 'FIFA', 'FIFA Online', 'ESPORTS', 'FIFA', NULL, NULL, 19, '2022-03-29 16:15:01', '2022-03-29 16:15:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5055, '2362', 96, 'Rocket League', '火箭联盟', 'ESPORTS', 'RocketLeague', NULL, NULL, 20, '2022-03-29 16:15:02', '2022-03-29 16:15:02', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5056, '2636', 96, 'CF', '穿越火线', 'ESPORTS', 'CF', NULL, NULL, 21, '2022-03-29 16:15:03', '2022-03-29 16:15:03', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5057, '2653', 96, 'CR', '皇室战争', 'ESPORTS', 'CR', NULL, NULL, 22, '2022-03-29 16:15:04', '2022-03-29 16:15:04', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5058, '2661', 96, 'MSG', '梦三国2', 'ESPORTS', 'MSG', NULL, NULL, 23, '2022-03-29 16:15:05', '2022-03-29 16:15:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5059, '2706', 96, 'QQ', 'QQ飞车', 'ESPORTS', 'QQ', NULL, NULL, 24, '2022-03-29 16:15:07', '2022-03-29 16:15:07', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5060, '2776', 96, 'Quake', '雷神之锤', 'ESPORTS', 'Quake', NULL, NULL, 25, '2022-03-29 16:15:08', '2022-03-29 16:15:08', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5061, '2867', 96, 'WoW', '魔兽世界', 'ESPORTS', 'WoW', NULL, NULL, 26, '2022-03-29 16:15:09', '2022-03-29 16:15:09', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5062, '2948', 96, 'Artifact', '神器', 'ESPORTS', 'Artifact', NULL, NULL, 27, '2022-03-29 16:15:10', '2022-03-29 16:15:10', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5063, '3138', 96, 'AOV', '传说对决', 'ESPORTS', 'AOV', NULL, NULL, 28, '2022-03-29 16:15:11', '2022-03-29 16:15:11', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5064, '3331', 96, 'GP', '和平精英', 'ESPORTS', 'GP', NULL, NULL, 29, '2022-03-29 16:15:12', '2022-03-29 16:15:12', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5065, '3332', 96, 'Valorant', '无畏契约', 'ESPORTS', 'Valorant', NULL, NULL, 30, '2022-03-29 16:15:13', '2022-03-29 16:15:13', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5066, '3334', 96, 'TT', '云顶之弈', 'ESPORTS', 'TT', NULL, NULL, 31, '2022-03-29 16:15:14', '2022-03-29 16:15:14', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5067, '3336', 96, 'NARAKA', '永劫无间', 'ESPORTS', 'NARAKA', NULL, NULL, 32, '2022-03-29 16:15:15', '2022-03-29 16:15:15', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);


#AVIA订单表
CREATE TABLE `game_order_avia`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `OrderID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `UserName` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户名',
  `Type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单类型Single电竞单关订单\r\nCombo电竞串关订单\r\nSmart趣味游戏订单\r\nAnchor主播订单\r\nVisualSport虚拟电竞订单',
  `Status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单状态 None等待开奖,Cancel比赛取消,Win赢,Lose输,Revoke无效订单',
  `BetAmount` decimal(10, 4) NOT NULL DEFAULT 0.0000 COMMENT '投注金额',
  `BetMoney` decimal(10, 4) NOT NULL DEFAULT 0.0000 COMMENT '有效投注金额',
  `Money` decimal(10, 4) NOT NULL DEFAULT 0.0000 COMMENT '盈亏金额',
  `CreateAt` datetime NULL DEFAULT NULL COMMENT '下单时间',
  `ResultAt` datetime NULL DEFAULT NULL COMMENT '结果产生时间',
  `RewardAt` datetime NULL DEFAULT NULL COMMENT '派奖时间',
  `UpdateAt` datetime NULL DEFAULT NULL COMMENT '订单数据的更新时间',
  `Timestamp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单更新的时间戳',
  `IP` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投注IP',
  `Language` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '语言环境',
  `Platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备',
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '币种',
  `IsTest` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '是否是测试订单',
  `ReSettlement` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '本订单重新结算的次数',
  `OddsType` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '盘口类型',
  `Odds` decimal(6, 4) NULL DEFAULT 0.0000 COMMENT '赔率',
  `ComboType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '串关类型',
  `Details` json NULL COMMENT '注单详细',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_number`(`OrderID`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '泛亚电竞' ROW_FORMAT = Dynamic;
