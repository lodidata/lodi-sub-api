#***前面所有涉及game_menu表和game_3th的操作都不处理


#新游戏分类
导出并覆盖game_menu表


#创建游戏订单表
CREATE TABLE `game_order_pg`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT ' 用户ID',
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '游戏账户playerName',
  `OCode` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '注单号betId',
  `gameCode` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '游戏代码gameId',
  `betAmount` int(10) NOT NULL DEFAULT 0 COMMENT '下注金额',
  `winAmount` int(10) NOT NULL COMMENT '下注结果赢得金额',
  `income` int(10) NOT NULL COMMENT '赢得金额-下注金额',
  `gameDate` datetime NOT NULL COMMENT '下注时间betEndTime',
  `parentBetId` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '母注单的唯一标识符',
  `betType` tinyint(3) NULL DEFAULT 1 COMMENT '投注记录的类别:1: 真实游戏',
  `transactionType` tinyint(3) NULL DEFAULT NULL COMMENT '交易类别：1: 现金2: 红利3: 免费游戏',
  `platform` tinyint(3) NULL DEFAULT 1 COMMENT '投注记录平台1windows,2',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '记录货币',
  `jackpotRtpContributionAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '玩家的奖池返还率贡献额',
  `jackpotContributionAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '玩家的奖池贡献额',
  `jackpotWinAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '玩家的奖池金额',
  `balanceBefore` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '玩家交易前的余额',
  `balanceAfter` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '玩家交易后的余额',
  `handsStatus` tinyint(3) NULL DEFAULT 3 COMMENT '投注状态：1: 非最后一手投注2：最后一手投注3：已调整',
  `rowVersion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据更新时间',
  `betTime` datetime NULL DEFAULT NULL COMMENT '当前投注的开始时间',
  `betEndTime` datetime NULL DEFAULT NULL COMMENT '当前投注的结束时间',
  `isFeatureBuy` tinyint(1) NULL DEFAULT NULL COMMENT '表示旋转类型：True：特色旋转False：普通旋转',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`OCode`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'PG电子' ROW_FORMAT = Dynamic;

CREATE TABLE `game_order_kmqm`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `ugsbetid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'QM内部投注辨识码',
  `txid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '来自游戏供应商的交易或投注辨识码',
  `betid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '外部的投注辨识码',
  `beton` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '记录在QM服务器的投注时间',
  `betclosedon` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投注关闭的时间',
  `betupdatedon` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '投注更新的时间',
  `timestamp` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商处理动作的时间戳',
  `roundid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏交易执行回合(round)时的游戏供应商辨识码',
  `roundstatus` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '报告时间的游戏回合状态Open/Closed',
  `userid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家专属辨识代码',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家的显示名称',
  `riskamt` decimal(10, 2) NULL DEFAULT NULL COMMENT '投注的总金额',
  `winamt` decimal(10, 2) NULL DEFAULT NULL COMMENT '投注赢的金额',
  `winloss` decimal(10, 2) NULL DEFAULT NULL COMMENT '投注的净总金额',
  `beforebal` decimal(10, 2) NULL DEFAULT NULL COMMENT '投注交易前玩家的余额',
  `postbal` decimal(10, 2) NULL DEFAULT NULL COMMENT '投注交易后玩家的余额',
  `cur` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家于QM系统使用的货币',
  `gameprovider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商的名称',
  `gameprovidercode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商代码',
  `gamename` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏名称',
  `gameid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商的专属游戏辨识代码',
  `platformtype` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏平台的类型',
  `ipaddress` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商报告的玩家IP地址',
  `bettype` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '永远是“PlaceBet”',
  `playtype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏供应商所定义的游戏类别，如BaBank、BaDeal, DtTigr、DtDrag等',
  `playertype` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用来辨识转账的玩家为真实玩家或测试玩家。 1代表真实玩家 2代表营运商测试玩家 4.代表QM测试玩家',
  `turnover` decimal(10, 2) NULL DEFAULT NULL COMMENT '回合中所有投注的流水额',
  `validbet` decimal(10, 2) NULL DEFAULT NULL COMMENT '回合中所有投注的有效投注总金额',
  `jackpotcontribution` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '有效投注总金额中，貢獻到jackpot的金額',
  `jackpotid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '玩家中奖的彩金池代号',
  `jackpotwinamt` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '玩家中奖的彩金金额',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`ugsbetid`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'KMQM棋牌游戏' ROW_FORMAT = DYNAMIC;

CREATE TABLE `game_order_tf`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `order_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `odds` decimal(10, 2) NOT NULL COMMENT '赔率 备注：如果是连串, OR, 1x2, SPOR，赔率是欧盘赔率。否则是马来赔率。',
  `malay_odds` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '马来赔率',
  `euro_odds` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '欧盘赔率',
  `member_odds` decimal(10, 2) NULL DEFAULT NULL COMMENT '会员下的赔率',
  `member_odds_style` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '会员下的盘euro hongkong indo  malay',
  `game_type_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏ID',
  `game_type_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏名称',
  `game_market_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '盘口名称',
  `market_option` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '盘口局分 match = 总局 map = 局',
  `map_num` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '第几局 MAP 1 = 第一局 Q1 = 第一节 FIRST HALF - 上半场 SECOND HALF - 下半场',
  `bet_type_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '盘口类型',
  `competition_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '比赛名称',
  `event_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '赛事ID',
  `event_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '赛事名称',
  `event_datetime` datetime NULL DEFAULT NULL COMMENT '赛事开始时间',
  `date_created` datetime NULL DEFAULT NULL COMMENT '下注时间',
  `settlement_datetime` datetime NULL DEFAULT NULL COMMENT '结算时间',
  `modified_datetime` datetime NULL DEFAULT NULL COMMENT '更改时间',
  `bet_selection` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下注选项',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '货币',
  `amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '下注金额',
  `settlement_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注单状况confirmed = 确定settled = 结算 cancelled = 取消',
  `is_unsettled` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '已重新结算 true,false',
  `result_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注单结果WIN = 赢LOSS = 输DRAW = 和CANCELLED = 取消',
  `result` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '盘口结果',
  `earnings` decimal(10, 2) NULL DEFAULT NULL COMMENT '输赢额',
  `handicap` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '让分数',
  `member_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '会员号',
  `request_source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下注的管道\r\ndesktop-browser = 电脑浏览器\r\nmobile-browser = 手机浏览器 (包括嵌入在APP里)\r\nmobile-app = 手机APP\r\nunknown = 未知\r\nnull = 旧数据没有',
  `is_combo` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '是否连串 true,false',
  `ticket_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注单下注状况db = 早盘live = 滚球',
  `tickets` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '只有在is_combo=true的情况才会有。里面的格式是跟以上一样',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'TF雷火电竞' ROW_FORMAT = Dynamic;

CREATE TABLE `game_order_sv388`  (
      `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT ' 用户ID',
  `gameType` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '平台游戏类型SLOT,FH,TABLE,LIVE,EGAME,ESPORTS,VIRTUAL,LOTTO',
  `winAmount` decimal(10, 2) NOT NULL COMMENT '返还金额 (包含下注金额)',
  `settleStatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态0 1 -1',
  `realBetAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实下注金额',
  `realWinAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实返还金额',
  `txTime` datetime NULL DEFAULT NULL COMMENT '交易时间 辨认交易时间依据',
  `updateTime` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `userId` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家 ID',
  `betType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT ' 游戏平台的下注项目',
  `platform` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏平台名称SEXYBCRT',
  `txStatus` tinyint(1) NULL DEFAULT 0 COMMENT '该交易当前状况',
  `betAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '下注金额',
  `gameName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏名称',
  `platformTxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商注单号',
  `betTime` datetime NULL DEFAULT NULL COMMENT '玩家下注时间',
  `gameCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '平台游戏代码',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'THB' COMMENT '玩家货币代码',
  `jackpotBetAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的下注金额',
  `jackpotWinAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的获胜金额',
  `turnover` decimal(10, 2) NULL DEFAULT NULL COMMENT '游戏平台有效投注',
  `roundId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商的回合识别码',
  `gameInfo` json NULL COMMENT '游戏讯息',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`platformTxId`) USING BTREE COMMENT '订单号',
  INDEX `user_id`(`user_id`) USING BTREE COMMENT '用户ID'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AWC集合-SV388斗鸡订单表' ROW_FORMAT = Dynamic;


CREATE TABLE `game_order_sgmk`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `ticketId` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '下注单号',
  `acctId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户标识 ID',
  `ticketTime` datetime NOT NULL COMMENT '下注时间',
  `categoryId` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏种类SM or TB or AD or BN',
  `gameCode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏代码',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '货币 ISO 代码',
  `betAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '下注金额',
  `result` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '结果',
  `winLoss` decimal(10, 2) NULL DEFAULT NULL COMMENT '用户输赢',
  `jackpotAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Jackpot',
  `betIp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户下注 IP',
  `luckyDrawId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `completed` tinyint(1) NULL DEFAULT 1 COMMENT '是否已结束',
  `roundId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏 log ID',
  `sequence` tinyint(3) NULL DEFAULT NULL COMMENT '0 =没赢 jackpot',
  `channel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注单来自手机或网',
  `balance` decimal(10, 2) NULL DEFAULT NULL COMMENT '上轮余额',
  `jpWin` decimal(10, 2) NULL DEFAULT NULL COMMENT '积宝赢额',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`ticketId`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'SGMK新霸电子' ROW_FORMAT = Dynamic;

CREATE TABLE `game_order_rbc`  (
    `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT ' 用户ID',
  `gameType` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '平台游戏类型SLOT,FH,TABLE,LIVE,EGAME,ESPORTS,VIRTUAL,LOTTO',
  `winAmount` decimal(10, 2) NOT NULL COMMENT '返还金额 (包含下注金额)',
  `settleStatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态0 1 -1',
  `realBetAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实下注金额',
  `realWinAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实返还金额',
  `txTime` datetime NULL DEFAULT NULL COMMENT '交易时间 辨认交易时间依据',
  `updateTime` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `userId` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家 ID',
  `betType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT ' 游戏平台的下注项目',
  `platform` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏平台名称SEXYBCRT',
  `txStatus` tinyint(1) NULL DEFAULT 0 COMMENT '该交易当前状况',
  `betAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '下注金额',
  `gameName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏名称',
  `platformTxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商注单号',
  `betTime` datetime NULL DEFAULT NULL COMMENT '玩家下注时间',
  `gameCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '平台游戏代码',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'THB' COMMENT '玩家货币代码',
  `jackpotBetAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的下注金额',
  `jackpotWinAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的获胜金额',
  `turnover` decimal(10, 2) NULL DEFAULT NULL COMMENT '游戏平台有效投注',
  `roundId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商的回合识别码',
  `gameInfo` json NULL COMMENT '游戏讯息',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`platformTxId`) USING BTREE COMMENT '订单号',
  INDEX `user_id`(`user_id`) USING BTREE COMMENT '用户ID'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AWC集合-HORSEBOOK订单表' ROW_FORMAT = Dynamic;


#新游戏

导出并覆盖game_3th表



#更新game_3th表图片 qp_img横版图片  game_img竖版图片

#game_menu表图片不动


#字段太小
ALTER TABLE `game_order_error` 
MODIFY COLUMN `error` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `json`;

#字段小数点不对
ALTER TABLE `game_order_png` 
MODIFY COLUMN `Balance` decimal(10, 2) NULL DEFAULT NULL COMMENT '用户余额' AFTER `ExternalTransactionId`;

#SEXYBCRT表迁移更新

#1、重命名表
RENAME TABLE `game_order_sexybcrt` TO `game_order_sexybcrt2`;

#2、创建新表

CREATE TABLE `game_order_sexybcrt` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT ' 用户ID',
  `gameType` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '平台游戏类型SLOT,FH,TABLE,LIVE,EGAME,ESPORTS,VIRTUAL,LOTTO',
  `winAmount` decimal(10, 2) NOT NULL COMMENT '返还金额 (包含下注金额)',
  `settleStatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态0 1 -1',
  `realBetAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实下注金额',
  `realWinAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '真实返还金额',
  `txTime` datetime NULL DEFAULT NULL COMMENT '交易时间 辨认交易时间依据',
  `updateTime` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `userId` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '玩家 ID',
  `betType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT ' 游戏平台的下注项目',
  `platform` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏平台名称SEXYBCRT',
  `txStatus` tinyint(1) NULL DEFAULT 0 COMMENT '该交易当前状况',
  `betAmount` decimal(10, 2) NULL DEFAULT NULL COMMENT '下注金额',
  `gameName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏名称',
  `platformTxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商注单号',
  `betTime` datetime NULL DEFAULT NULL COMMENT '玩家下注时间',
  `gameCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '平台游戏代码',
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'THB' COMMENT '玩家货币代码',
  `jackpotBetAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的下注金额',
  `jackpotWinAmount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累积奖金的获胜金额',
  `turnover` decimal(10, 2) NULL DEFAULT NULL COMMENT '游戏平台有效投注',
  `roundId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '游戏商的回合识别码',
  `gameInfo` json NULL COMMENT '游戏讯息',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_id`(`platformTxId`) USING BTREE COMMENT '订单号',
  INDEX `user_id`(`user_id`) USING BTREE COMMENT '用户ID'
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AWC集合-SEXYBCRT订单表' ROW_FORMAT = Dynamic;

#3、迁移数据

INSERT into game_order_sexybcrt (user_id,gameType,winAmount,settleStatus,realBetAmount,realWinAmount,txTime,updateTime,userId,betType,platform,txStatus,betAmount,gameName,platformTxId,betTime,gameCode,currency,jackpotBetAmount,jackpotWinAmount,turnover,roundId,gameInfo) 
SELECT user_id,'LIVE' as gameType,winAmount/100 as winAmount,0 as settleStatus,betAmount/100 as realBetAmount,winAmount/100 as realWinAmount, gameDate as txTime, gameDate as updateTime, Username as userId, 'Player' as betType, 'SEXYBCRT' as platform,1 as txStatus,betAmount/100 as betAmount,g3.game_name as gameName, OCode as platformTxId, gameDate as betTime,gameCode,'THB' as currency, 0 as jackpotBetAmount, 0 as jackpotWinAmount, betAmount/100 as turnover,1 as roundId, '' as gameInfo
from game_order_sexybcrt2 g LEFT JOIN game_3th g3 on g.gameCode=g3.kind_id;