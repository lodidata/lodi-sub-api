
#FC游戏配置
INSERT INTO `game_api` (`id`, `type`, `name`, `lobby`, `cagent`, `des_key`, `key`, `pub_key`, `loginUrl`, `orderUrl`, `apiUrl`, `update_at`) VALUES (82, 'FC', 'FC电子', '', '220TG', NULL, '1eka2IRzydUOQWst', NULL, '', 'https://api.fcg666.net', 'https://api.fcg666.net', NULL);


#FC游戏菜单
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (93, 4, 'FC', 'FCSLOT', 'FC', 'FC电子', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'enabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (94, 22, 'FCBY', 'FCFH', 'FC', 'FC捕鱼', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'enabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');



#FC游戏
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES 
(4992, '22016', 93, 'GOLDEN PANTHER', '金钱豹', 'slot', 'GOLDENPANTHER', NULL, NULL, 10, '2022-03-24 15:28:33', '2022-03-24 15:28:33', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4993, '22017', 93, 'THREE LITTLE PIGS', '三只小猪', 'slot', 'THREELITTLEPIGS', NULL, NULL, 11, '2022-03-24 15:28:34', '2022-03-24 15:28:34', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4994, '22018', 93, 'NIGHT MARKET', '逛夜市', 'slot', 'NIGHTMARKET', NULL, NULL, 12, '2022-03-24 15:28:35', '2022-03-24 15:28:35', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4995, '22019', 93, 'PANDA DRAGON BOAT', '熊猫龙舟', 'slot', 'PANDADRAGONBOAT', NULL, NULL, 13, '2022-03-24 15:28:36', '2022-03-24 15:28:36', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4996, '22020', 93, 'CHINESE NEW YEAR', '大过年', 'slot', 'CHINESENEWYEAR', NULL, NULL, 14, '2022-03-24 15:28:38', '2022-03-24 15:28:38', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4997, '22021', 93, 'PONG PONG HU', '碰碰胡', 'slot', 'PONGPONGHU', NULL, NULL, 15, '2022-03-24 15:28:39', '2022-03-24 15:28:39', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4998, '22022', 93, 'FORTUNE KOI', '锦鲤跃钱', 'slot', 'FORTUNEKOI', NULL, NULL, 16, '2022-03-24 15:28:40', '2022-03-24 15:28:40', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4999, '22023', 93, 'DA LE MEN', '大乐门', 'slot', 'DALEMEN', NULL, NULL, 17, '2022-03-24 15:28:41', '2022-03-24 15:28:41', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5000, '22024', 93, 'ANIMAL RACING', '龟兔赛车', 'slot', 'ANIMALRACING', NULL, NULL, 18, '2022-03-24 15:28:42', '2022-03-24 15:28:42', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5001, '22026', 93, 'HOT POT PARTY', '火锅派对', 'slot', 'HOTPOTPARTY', NULL, NULL, 19, '2022-03-24 15:28:43', '2022-03-24 15:28:43', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5002, '22027', 93, 'HAPPY DUO BAO', '开心夺宝', 'slot', 'HAPPYDUOBAO', NULL, NULL, 20, '2022-03-24 15:28:44', '2022-03-24 15:28:44', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5003, '22028', 93, 'TREASURE CRUISE', '寻宝奇航', 'slot', 'TREASURECRUISE', NULL, NULL, 21, '2022-03-24 15:28:45', '2022-03-24 15:28:45', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5004, '22029', 93, 'COWBOYS', '西部风云', 'slot', 'COWBOYS', NULL, NULL, 22, '2022-03-24 15:28:46', '2022-03-24 15:28:46', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5005, '22030', 93, 'LUXURY GOLDEN PANTHER', '豪华金钱豹', 'slot', 'LUXURYGOLDENPANTHER', NULL, NULL, 23, '2022-03-24 15:28:47', '2022-03-24 15:28:47', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5006, '22031', 93, 'WAR OF THE UNIVERSE', '宇宙大战', 'slot', 'WAROFTHEUNIVERSE', NULL, NULL, 24, '2022-03-24 15:28:48', '2022-03-24 15:28:48', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5007, '22032', 93, 'MAGIC BEANS', '魔豆', 'slot', 'MAGICBEANS', NULL, NULL, 25, '2022-03-24 15:28:49', '2022-03-24 15:28:49', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5008, '22034', 93, 'GOLD RUSH', '淘金乐', 'slot', 'GOLDRUSH', NULL, NULL, 26, '2022-03-24 15:28:50', '2022-03-24 15:28:50', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5009, '22036', 93, 'RICH MAN', '富贵大亨', 'slot', 'RICHMAN', NULL, NULL, 27, '2022-03-24 15:28:51', '2022-03-24 15:28:51', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4988, '21003', 94, 'MONKEY KING FISHING', '大圣捕鱼', 'fish', 'MONKEYKINGFISHING', NULL, NULL, 5, '2022-03-24 15:28:28', '2022-03-24 15:28:28', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4989, '21004', 94, 'BAO CHUAN FISHING', '宝船捕鱼', 'fish', 'BAOCHUANFISHING', NULL, NULL, 6, '2022-03-24 15:28:30', '2022-03-24 15:28:30', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4990, '21006', 94, 'FIERCE FISHING', '激斗捕鱼', 'fish', 'FIERCEFISHING', NULL, NULL, 7, '2022-03-24 15:28:31', '2022-03-24 15:28:31', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4991, '21007', 94, 'FA CHAI FISHING', '发财捕鱼', 'fish', 'FACHAIFISHING', NULL, NULL, 8, '2022-03-24 15:28:32', '2022-03-24 15:28:32', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);


#FC游戏打码量
ALTER TABLE `user_dml` 
ADD COLUMN `FC` int(10) UNSIGNED NULL DEFAULT 0 AFTER `CGQP`,
ADD COLUMN `FCBY` int(10) UNSIGNED NULL DEFAULT 0 AFTER `FC`;

#更新FC游戏图片
update game_3th set game_img=CONCAT('https://update.a1jul.com/kgb/game/vert/fc/',kind_id,'.png') where game_id in (93,94);


DROP TABLE IF EXISTS `game_order_fc`;
CREATE TABLE `game_order_fc`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `recordID` bigint(20) NOT NULL COMMENT '游戏记录编号（唯一码）',
  `account` varchar(20) NOT NULL COMMENT '玩家账号',
  `gameID` int(10) NOT NULL COMMENT '游戏编号',
  `gametype` tinyint(1) NOT NULL COMMENT '游戏类型1捕鱼机2老虎机7特色游戏',
  `bet` double(10, 2) NOT NULL COMMENT '下注点数',
  `winlose` double(10, 2) NOT NULL COMMENT '输赢点数（含下注）',
  `prize` double(10, 2) NOT NULL COMMENT '赢分点数',
  `jpmode` int(10) NULL DEFAULT 0 COMMENT '彩金模式',
  `jppoints` double(10, 2) NULL DEFAULT NULL COMMENT '彩金点数',
  `jptax` double(10, 6) NULL DEFAULT NULL COMMENT '彩金抽水 (支持到小数第六位)',
  `before` double(10, 2) NULL DEFAULT NULL COMMENT '下注前点数',
  `after` double(10, 2) NULL DEFAULT NULL COMMENT '下注后点数',
  `bdate` datetime NOT NULL COMMENT '下注时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_number`(`recordID`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'FC发财游戏' ROW_FORMAT = DYNAMIC;
