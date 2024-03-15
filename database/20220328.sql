#UG体育
INSERT INTO `game_api` (`id`, `type`, `name`, `lobby`, `cagent`, `des_key`, `key`, `pub_key`, `loginUrl`, `orderUrl`, `apiUrl`, `update_at`) VALUES (83, 'UG', 'UF体育', '', 'TG', NULL, 'f8Snn3f4F8npGexDpWDMazRbCArZ5GW3', NULL, '', 'http://transferapi.ugamingservice888.com', 'http://transferapi.ugamingservice888.com', NULL);

INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (95, 16, 'UG', 'UGSPORT', 'UG', 'UG体育', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'enabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');


ALTER TABLE `user_dml` 
ADD COLUMN `UG` int(10) UNSIGNED NULL DEFAULT 0 AFTER `FCBY`;


INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES 
(5010, '1', 95, 'Soccer', '足球', 'SPORT', 'Soccer', NULL, NULL, 1, '2022-03-28 16:19:14', '2022-03-28 16:19:14', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5019, '10', 95, 'Golf', '高尔夫球', 'SPORT', 'Golf', NULL, NULL, 10, '2022-03-28 16:19:24', '2022-03-28 16:19:24', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5020, '11', 95, 'Cricket', '板球', 'SPORT', 'Cricket', NULL, NULL, 11, '2022-03-28 16:19:25', '2022-03-28 16:19:25', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5021, '12', 95, 'Volleyball', '排球', 'SPORT', 'Volleyball', NULL, NULL, 12, '2022-03-28 16:19:26', '2022-03-28 16:19:26', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5022, '13', 95, 'Handball', '手球', 'SPORT', 'Handball', NULL, NULL, 13, '2022-03-28 16:19:27', '2022-03-28 16:19:27', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5023, '14', 95, 'Water Polo', '水球', 'SPORT', 'WaterPolo', NULL, NULL, 14, '2022-03-28 16:19:28', '2022-03-28 16:19:28', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5024, '15', 95, 'Beach Volleyball', '沙滩足球', 'SPORT', 'BeachVolleyball', NULL, NULL, 15, '2022-03-28 16:19:29', '2022-03-28 16:19:29', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5025, '16', 95, 'Indoor Soccer', '室内足球', 'SPORT', 'IndoorSoccer', NULL, NULL, 16, '2022-03-28 16:19:30', '2022-03-28 16:19:30', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5026, '17', 95, 'Snooker', '斯诺克', 'SPORT', 'Snooker', NULL, NULL, 17, '2022-03-28 16:19:31', '2022-03-28 16:19:31', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5027, '18', 95, 'Football', '橄榄球', 'SPORT', 'Football', NULL, NULL, 18, '2022-03-28 16:19:32', '2022-03-28 16:19:32', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5028, '19', 95, 'Racing Car', '赛车', 'SPORT', 'RacingCar', NULL, NULL, 19, '2022-03-28 16:19:33', '2022-03-28 16:19:33', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5011, '2', 95, 'Basketball', '篮球', 'SPORT', 'Basketball', NULL, NULL, 2, '2022-03-28 16:19:15', '2022-03-28 16:19:15', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5029, '20', 95, 'Darts', '飞镖', 'SPORT', 'Darts', NULL, NULL, 20, '2022-03-28 16:19:34', '2022-03-28 16:19:34', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5030, '21', 95, 'Boxing', '拳击', 'SPORT', 'Boxing', NULL, NULL, 21, '2022-03-28 16:19:35', '2022-03-28 16:19:35', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5031, '22', 95, 'Athletics', '田径', 'SPORT', 'Athletics', NULL, NULL, 22, '2022-03-28 16:19:36', '2022-03-28 16:19:36', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5032, '23', 95, 'Bicycle', '自行车', 'SPORT', 'Bicycle', NULL, NULL, 23, '2022-03-28 16:19:37', '2022-03-28 16:19:37', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5033, '24', 95, 'Entertainment', '娱乐', 'SPORT', 'Entertainment', NULL, NULL, 24, '2022-03-28 16:19:38', '2022-03-28 16:19:38', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5034, '25', 95, 'Winter Sport', '冬季运动', 'SPORT', 'WinterSport', NULL, NULL, 25, '2022-03-28 16:19:39', '2022-03-28 16:19:39', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5035, '26', 95, 'E-Sports', '电子竞技', 'SPORT', 'E-Sports', NULL, NULL, 26, '2022-03-28 16:19:40', '2022-03-28 16:19:40', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5012, '3', 95, 'American Football', '美式足球', 'SPORT', 'AmericanFootball', NULL, NULL, 3, '2022-03-28 16:19:16', '2022-03-28 16:19:16', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5013, '4', 95, 'Baseball', '棒球', 'SPORT', 'Baseball', NULL, NULL, 4, '2022-03-28 16:19:17', '2022-03-28 16:19:17', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5014, '5', 95, 'Hockey', '曲棍球', 'SPORT', 'Hockey', NULL, NULL, 5, '2022-03-28 16:19:19', '2022-03-28 16:19:19', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5015, '6', 95, 'Long Hockey', '长曲棍球', 'SPORT', 'LongHockey', NULL, NULL, 6, '2022-03-28 16:19:20', '2022-03-28 16:19:20', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5016, '7', 95, 'Tennis', '网球', 'SPORT', 'Tennis', NULL, NULL, 7, '2022-03-28 16:19:21', '2022-03-28 16:19:21', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5017, '8', 95, 'Badminton', '羽毛球', 'SPORT', 'Badminton', NULL, NULL, 8, '2022-03-28 16:19:22', '2022-03-28 16:19:22', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(5018, '9', 95, 'Table Tennis', '乒乓球', 'SPORT', 'TableTennis', NULL, NULL, 9, '2022-03-28 16:19:23', '2022-03-28 16:19:23', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);

#UG注单表
DROP TABLE IF EXISTS `game_order_ug`;
CREATE TABLE `game_order_ug`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `BetID` varchar(32) NULL DEFAULT NULL COMMENT '注单号',
  `GameID` int(5) UNSIGNED NOT NULL COMMENT '游戏编号 ( 1 = 体育)',
  `SubGameID` int(5) UNSIGNED NOT NULL COMMENT '子游戏编号  (子游戏编号于SportID 一样)\r\n(此编号可以查询 ',
  `Account` varchar(20) NOT NULL COMMENT '会员账号',
  `Currency` varchar(5) NOT NULL COMMENT '货币',
  `BetAmount` decimal(10, 4) NOT NULL COMMENT '下注金额',
  `BetOdds` decimal(10, 4) NOT NULL COMMENT '投注赔率',
  `AllWin` decimal(10, 4) NOT NULL COMMENT '全赢',
  `DeductAmount` decimal(10, 4) NOT NULL COMMENT '扣款金额 ( 扣除投注金额)',
  `BackAmount` decimal(10, 4) NULL DEFAULT NULL COMMENT '退还金额',
  `Win` decimal(10, 4) NULL DEFAULT NULL COMMENT '赢输 (负数 = 输 / 正数=赢) ',
  `Turnover` decimal(10, 4) NULL DEFAULT NULL COMMENT '有效投注金额',
  `OddsStyle` varchar(2)  NULL DEFAULT NULL COMMENT '赔率样式',
  `BetDate` datetime NULL DEFAULT NULL COMMENT '投注时间',
  `Status` tinyint(1) NULL DEFAULT NULL COMMENT '投注状态 (返回)\r\n0 = 等待\r\n1 = 接受\r\n2 = 结算\r\n3 = 取消\r\n4 = 拒绝',
  `Result` varchar(32) NULL DEFAULT NULL COMMENT '注单结果\r\n0 = 和\r\n1 = 全赢\r\n2 = 全输\r\n3 = 赢半\r\n4 = 输半',
  `ReportDate` datetime NULL DEFAULT NULL COMMENT '注单报表时间',
  `BetIP` varchar(32) NULL DEFAULT NULL COMMENT '投注IP',
  `UpdateTime` datetime NULL DEFAULT NULL COMMENT '注单更新时间',
  `BetInfo` json NULL COMMENT '投注内容',
  `BetResult` json NULL COMMENT '投注结果',
  `BetType` smallint(5) NULL DEFAULT NULL COMMENT '玩法 \r\nBetType和 Betinfo 里面的MarketID 是一样. ',
  `BetPos` smallint(5) NULL DEFAULT NULL COMMENT '投注位置',
  `AgentID` varchar(32) NULL DEFAULT NULL COMMENT '代理编号',
  `SortNo` bigint(16) UNSIGNED NOT NULL COMMENT '排序编号',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_number`(`BetID`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'UG体育注单' ROW_FORMAT = Dynamic;
