#DS88接口配置
INSERT INTO game_api` (`type`, `name`, `key`, `orderUrl`, `apiUrl`) VALUES ('DS88', 'DS88', 'eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MzU5LCJlbWFpbCI6ImRzODgtcnQyMDVAZHM4OHNhYm9uZy54eXoifQ.rWvTrULLPQiN3yLySav3xQyTYbgGhyqJHAUbDAWVg1U', 'https://api.ds88sabong.xyz', 'https://api.ds88sabong.xyz');

#添加大类斗鸡
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `status`, `switch`, `across_status`) VALUES (24, 0, 'SABONG', 'SABONG', 'SABONG', '斗鸡', 'enabled', 'enabled', 'enabled');

#DS88游戏菜单
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `status`, `switch`, `across_status`) VALUES (100, 24, 'DS88', 'DS88', 'DS88', 'DS88斗鸡', 'enabled', 'enabled', 'enabled');
#DS88游戏
INSERT INTO game_3th` (`kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`) VALUES ('100', 100, 'DS88', 'DS88', 'SABONG', 'DS88');
#DS88打码量
ALTER TABLE `user_dml` ADD COLUMN ` DS88` int(10) UNSIGNED NULL DEFAULT 0 AFTER `AVIA`;

#DS88注单表
CREATE TABLE `game_order_ds88`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL COMMENT '用户ID',
  `slug` varchar(255) NULL COMMENT '投注單號 Bet Number(unique)',
  `arena_fight_no` varchar(255) NULL COMMENT '賽事代號',
  `round_id` bigint(20) NULL COMMENT 'Round Id',
  `fight_no` bigint(20) NULL COMMENT 'Fight No',
  `side` varchar(255) NULL COMMENT '下注哪一邊',
  `account` varchar(20) NULL COMMENT '會員帳號名稱',
  `status` varchar(20) NULL COMMENT '狀態( init beted settled cancel fail )\r\ninit: 注單初始\r\nbeted:注單成立\r\nsettled:派彩\r\ncancel:取消\r\nfail:失敗',
  `odd` decimal(14, 2) NULL COMMENT '賠率',
  `bet_amount` decimal(14, 2) NULL COMMENT '下注金額',
  `net_income` decimal(14, 2) NULL COMMENT '淨收入',
  `bet_return` decimal(14, 2) NULL COMMENT '返回金額',
  `valid_amount` decimal(14, 2) NULL COMMENT '有效投注',
  `result` varchar(255) NULL COMMENT '結果（wala meron draw cancel ) 注意: 未宣判會是 Null',
  `is_settled` tinyint(1) NULL COMMENT '是否已結算',
  `bet_at` datetime(6) NULL COMMENT '投注時間',
  `settled_at` datetime(6) NULL COMMENT '結算時間',
  PRIMARY KEY (`id`),
  INDEX `user_id`(`user_id`) USING BTREE,
  UNIQUE INDEX `order_number`(`slug`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'DS88游戏注单';



INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES 
('rakeBack', '斗鸡返佣', 'int', 'SABONG', '10', NULL, 'disabled', NULL),
('audit', '斗鸡稽核', 'int', 'SABONG', '100', NULL, 'enabled', '2019-02-26 20:08:01');
