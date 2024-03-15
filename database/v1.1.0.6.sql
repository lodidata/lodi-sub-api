#增加CQ9游戏


ALTER TABLE `user_dml` 
ADD COLUMN `CQ9TAB` int(10) UNSIGNED NULL DEFAULT 0 AFTER `CQ9JJ`;

#增加CQ9桌牌
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (86, 20, 'CQ9TAB', 'CQ9TABLE', 'CQ9', 'CQ9桌牌', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'disabled', NULL, 'disabled', NULL, NULL, NULL, 'disabled');


#更新原CQ9街机游戏归类
UPDATE game_3th set game_id=85,type='Arcade' where id in (3603,3648,3656,3708,3722,3724,3751,3788,3811,3813);

#更新游戏
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4569, '60', 85, 'เกมจังเกิล ปาร์ตี้', '丛林舞会', 'Arcade', 'JungleParty', NULL, NULL, 73, '2022-03-02 11:08:05', '2022-03-02 11:08:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4573, '61', 85, 'มิสเตอร์บีน', '天天吃豆', 'Arcade', 'Mr.Bean', NULL, NULL, 163, '2022-03-02 11:08:15', '2022-03-02 11:08:15', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4571, 'GO01', 85, 'ดราก้อนบอล Dozer', '龙珠推币机', 'Arcade', 'DragonBallDozer', NULL, NULL, 149, '2022-03-02 11:08:11', '2022-03-02 11:08:11', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4575, 'GO169', 85, 'ดราก้อนปาจิงโกะ', '招财龙小钢珠', 'Arcade', 'DragonPachinko', NULL, NULL, 240, '2022-03-02 11:08:20', '2022-03-02 11:08:20', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4572, 'BT02', 86, 'ลูกเต๋าเจ้ามือ กระทิง-กระทิง', '抢庄骰子牛牛', 'Table', 'BankerDiceBull-Bull', NULL, NULL, 153, '2022-03-02 11:08:12', '2022-03-02 11:08:12', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4570, 'BT03', 86, 'Peeking Banker Bull-Bull', '抢庄眯牌牛牛', 'Table', 'PeekingBankerBull-Bull', NULL, NULL, 132, '2022-03-02 11:08:10', '2022-03-02 11:08:10', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4568, 'CE09', 86, 'Landlord Fights Carnival', '加倍斗地主', 'Table', 'LandlordFightsCarnival', NULL, NULL, 6, '2022-03-02 11:08:01', '2022-03-02 11:08:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES (4574, 'CH03', 86, 'ป๊อกเด้ง', '博丁', 'Table', 'PokDeng', NULL, NULL, 175, '2022-03-02 11:08:16', '2022-03-02 11:08:16', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);

#开启游戏分类
update game_menu set `status`='enabled',switch='enabled',across_status='enabled' where id in (85,86);

UPDATE `game_menu` SET `name` = 'SGMKARCADE' WHERE `id` = 82;
UPDATE `game_menu` SET `name` = 'CQ9ARCADE' WHERE `id` = 85;


#创建CQ9桌牌订单表

CREATE TABLE `game_order_cqnine_table`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注單狀態',
  `gamehall` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲商名稱',
  `gametype` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲種類',
  `gameplat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲平台',
  `gamecode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲代碼',
  `round` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲局號',
  `balance` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '遊戲後餘額',
  `win` int(11) NOT NULL DEFAULT 0 COMMENT '遊戲輸贏',
  `bet` int(11) NOT NULL DEFAULT 0 COMMENT '下注金額',
  `jackpot` int(11) UNSIGNED ZEROFILL NOT NULL DEFAULT 00000000000 COMMENT '彩池獎金',
  `jackpotcontribution` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '彩池奖金贡献值',
  `jackpottype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '彩池奖金类别',
  `validbet` int(11) NOT NULL DEFAULT 0 COMMENT '有效下注额',
  `endroundtime` datetime NULL DEFAULT NULL COMMENT '遊戲結束時間，格式為 RFC3339',
  `bettime` datetime NOT NULL COMMENT '下注时间',
  `createtime` datetime NULL DEFAULT NULL COMMENT '當筆資料建立時間',
  `singlerowbet` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '[true|false]是否為再旋轉形成的注單',
  `ticketid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '免费券id',
  `tickettype` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '免费券类型',
  `giventype` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '免费券取得类型',
  `gamerole` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '庄(banker) or 閒(player)',
  `bankertype` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'PC(pc) or 真人(human)',
  `rake` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '抽水金額',
  `roomfee` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '开房费用',
  `bettype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下注玩法',
  `gameresult` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏结果',
  `tabletype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '真人注单参数说明名称(1=百家，4=龙虎)',
  `tableid` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '桌号',
  `detail` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `roundnumber` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '局号',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_number`(`round`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'cq9-桌牌' ROW_FORMAT = Dynamic;



#替换CQ9新游戏图片
update game_3th set qp_img=CONCAT('https://update.a1jul.com/kgb/game/cq9/',kind_id,'.png'),game_img=CONCAT('https://update.a1jul.com/kgb/game/vert/cq9/',kind_id,'.png') where game_id in (85,86) and qp_img is null;