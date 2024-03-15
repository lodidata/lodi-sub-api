


#+++++++++++++++++++++++++++++++lodi_super_admin++++++++++++++++++++++++++++++++++++++++++++++++

#创建YGG游戏详情注单表

CREATE TABLE `lodi_super_admin`.`game_order_ygg_detail`  (
  `id` bigint(20) UNSIGNED NOT NULL  COMMENT '第三方ID',
  `reference` varchar(255)  NOT NULL COMMENT '注单号',
  `subreference` varchar(255)  NOT NULL COMMENT '注单号',
  `loginname` varchar(20)  NULL DEFAULT NULL COMMENT '用户名',
  `currency` varchar(20) NULL DEFAULT NULL COMMENT '货币',
  `type` varchar(20)  NULL DEFAULT NULL COMMENT '投注类型',
  `amount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '投注金额',
  `afterAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '投注前金额',
  `beforeAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '投注后金额',
  `gameName` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `DCGameID` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏ID',
  `createTime` datetime  DEFAULT NULL COMMENT '记录时间',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否同步：1同步，0未同步',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_reference`(`reference`) USING BTREE,
  INDEX `index_gameDate`(`createTime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'YGG原始注单';


#创建XG游戏注单表

CREATE TABLE `lodi_super_admin`.`game_order_xg`  (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`tid` tinyint(11) UNSIGNED NOT NULL COMMENT '大厅id',
`Account` varchar(50) NOT NULL DEFAULT '' COMMENT '會員帳號',
`WagersId` int(11) NOT NULL COMMENT '注單編號',
`GameId` varchar(50) NOT NULL DEFAULT '' COMMENT '遊戲代號',
`GameType` tinyint(11) NOT NULL COMMENT '遊戲類別\r\n百家樂	1\r\n骰寶	2	\r\n輪盤	3\r\n龍虎	5	\r\n色碟	6	\r\n極速骰寶	7',
`BetAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '下注金額',
`validBetAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '有效金額',
`WagersTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下注時間',
`PayoffTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新時間',
`SettlementTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '歸帳時間',
`PayoffAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '輸贏',
`prize_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '派奖金额',
`Commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退水',
`Jackpot` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '獎金',
`Status` tinyint(11) NOT NULL COMMENT '注單狀態\r\n1	中獎\r\n2	未中獎\r\n3	和局\r\n4	進行中\r\n6	取消單\r\n7	改單',
`Contribution` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '貢獻金',
`GameCategoryId` tinyint(11) NOT NULL COMMENT '遊戲類型1:電子2:真人3:彩票4:體育5:捕魚6:棋牌',
`Currency` varchar(20) NOT NULL DEFAULT '' COMMENT '币种',
`GameMethod` tinyint(11) NOT NULL COMMENT '遊戲玩法.\r\n百家樂標準	1\r\n百家樂西洋	2\r\n百家樂免水	3',
`TableType` tinyint(11) NOT NULL COMMENT '桌檯類型\r\n標準百家樂	1\r\n先發百家樂	2\r\n極速百家樂	3\r\n眯牌百家樂	4',
`TableId` varchar(20) NOT NULL DEFAULT '' COMMENT '遊戲局桌檯Id',
`Round` varchar(100) NOT NULL DEFAULT '' COMMENT '輪號',
`Run` int(11) NOT NULL COMMENT '局號',
`GameResult` varchar(100) NOT NULL DEFAULT '' COMMENT '開牌結果',
`BetType` json NOT NULL COMMENT '下注區列表',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE INDEX `uniqe_order`(`WagersId`,`WagersTime`) USING BTREE,
INDEX `indx_bettime`(`WagersTime`) USING BTREE,
INDEX `indx_tid`(`tid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = 'XG视讯注单表';



INSERT INTO `lodi_super_admin`.`game_api` (`id`, `type`, `name`, `currency`, `lobby`, `cagent`, `des_key`, `key`, `pub_key`, `loginUrl`, `orderUrl`, `apiUrl`, `update_at`, `site_type`) VALUES(92, 'XG', 'XG', 'PHP', '221,222,223,224,225,226,227,228,229,230', '874b890d-bd5a-429a-a30d-270f9bf74216', NULL, 'b29c6378-cf4c-4414-b8f1-dcfc5764564c', NULL, '', 'https://agent.x-gaming.bet/api/keno-api/xg-casino/', 'https://agent.x-gaming.bet/api/keno-api/keno-api/xg-casino/', '2022-07-20 18:32:04', 'lodi');


INSERT INTO `lodi_super_admin`.`game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`,`status``switch`, `across_status`) VALUES (109, 15, 'XG', 'XG', 'XG', 'XG', 'enabled', 'enabled','enabled');




#++++++++++++++lodi超管redis++++++++++++++++++++++++
del api_third__game_jump_data


#+++++++++++++++++++++++++++++++++lodi子站++++++++++++++++++++++++++++++++++++++++++++++++++++++++


ALTER TABLE `user_dml` ADD COLUMN `XG` int(10) UNSIGNED NULL DEFAULT 0;

CREATE TABLE `game_order_xg`  (
 `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
 `user_id` int(11) UNSIGNED NOT NULL COMMENT '用户id',
 `Account` varchar(50) NOT NULL DEFAULT '' COMMENT '會員帳號',
 `WagersId` int(11) NOT NULL COMMENT '注單編號',
 `GameId` varchar(50) NOT NULL DEFAULT '' COMMENT '遊戲代號',
 `GameType` tinyint(11) NOT NULL COMMENT '遊戲類別\r\n百家樂	1\r\n骰寶	2	\r\n輪盤	3\r\n龍虎	5	\r\n色碟	6	\r\n極速骰寶	7',
 `BetAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '下注金額',
 `validBetAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '有效金額',
 `WagersTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下注時間',
 `PayoffTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新時間',
 `SettlementTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '歸帳時間',
 `PayoffAmount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '輸贏',
 `prize_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '派奖金额',
 `Commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退水',
 `Jackpot` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '獎金',
 `Status` tinyint(11) NOT NULL COMMENT '注單狀態\r\n1	中獎\r\n2	未中獎\r\n3	和局\r\n4	進行中\r\n6	取消單\r\n7	改單',
 `Contribution` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '貢獻金',
 `GameCategoryId` tinyint(11) NOT NULL COMMENT '遊戲類型1:電子2:真人3:彩票4:體育5:捕魚6:棋牌',
 `Currency` varchar(20) NOT NULL DEFAULT '' COMMENT '币种',
 `GameMethod` tinyint(11) NOT NULL COMMENT '遊戲玩法.\r\n百家樂標準	1\r\n百家樂西洋	2\r\n百家樂免水	3',
 `TableType` tinyint(11) NOT NULL COMMENT '桌檯類型\r\n標準百家樂	1\r\n先發百家樂	2\r\n極速百家樂	3\r\n眯牌百家樂	4',
 `TableId` varchar(20) NOT NULL DEFAULT '' COMMENT '遊戲局桌檯Id',
 `Round` varchar(100) NOT NULL DEFAULT '' COMMENT '輪號',
 `Run` int(11) NOT NULL COMMENT '局號',
 `GameResult` varchar(100) NOT NULL DEFAULT '' COMMENT '開牌結果',
 `BetType` json NOT NULL COMMENT '下注區列表',
 PRIMARY KEY (`id`) USING BTREE,
 UNIQUE INDEX `uniqe_order`(`WagersId`,`WagersTime`) USING BTREE,
 INDEX `indx_bettime`(`WagersTime`) USING BTREE,
 INDEX `indx_userId`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = 'XG视讯注单表';

#更新图片 【注意】图片域名

INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`,`img`,`sort`, `status`, `switch`, `across_status`) VALUES(109, 15, 'XG', 'XG', 'XG', 'XG', 'https://img.caacaya.com/lodi/menu/xg.png',14, 'disabled', 'enabled','enabled');

INSERT INTO `game_3th` (`kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `game_img`) VALUES ('xg006', 109, 'xg','xg','LIVE','xg','https://img.caacaya.com/lodi/game/vert/xg/xg006.png');

#++++++++++++lodi子站redis
del game_jump_url