#第三方游戏返水重构
CREATE TABLE `rebet_exec`  (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`date` date NOT NULL DEFAULT '0000-00-00' COMMENT '返水日期',
`type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型（1：游戏，2：彩票）',
`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`) USING BTREE,
UNIQUE INDEX `udx_date_type`(`date`, `type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '返水执行记录表';

#代理盈亏返佣记录表
CREATE TABLE `agent_loseearn_bkge`  (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`deal_log_no` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '交易流水号(funds_deal_log里的order_number)',
`count_date` date NULL DEFAULT '0000-00-00' COMMENT '统计日期（周月结算 用统计周期的最后一天）',
`date` date NOT NULL DEFAULT '0000-00-00' COMMENT '日期(格式 2022-03-04)',
`user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
`user_name` varchar(30) NOT NULL DEFAULT '' COMMENT '用户名称',
`agent_name` varchar(30)  NOT NULL DEFAULT '' COMMENT '上级代理名称',
`agent_cnt` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下级代理人数(与rpt_agent里的一样)',
`num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注单数目',
`bkge` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '返佣金额',
`settle_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '结算金额',
`bet_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '总流水（总投注）',
`dml_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '打码量',
`fee_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '扣款金额',
`loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '总盈亏',
`bet_amount_list` json NOT NULL COMMENT '游戏投注列表',
`loseearn_amount_list` json NOT NULL COMMENT '盈亏列表',
`bkge_list` json NOT NULL COMMENT '游戏返佣列表',
`proportion_list` json NOT NULL COMMENT '占成列表(盈亏占比)',
`fee_list` json NOT NULL COMMENT '扣款列表',
`status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1：已发放，0：未发放',
`bkge_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '返佣时间',
`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE INDEX `udx_date_user_id`(`date`, `user_id`) USING BTREE,
INDEX `idx_count_date`(`count_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理盈亏返佣记录表';

#代理盈亏返佣 成本设置
CREATE TABLE `agent_loseearn_fee`  (
`id` tinyint(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
`name` varchar(20) NULL DEFAULT NULL COMMENT '项目名',
`type` tinyint(6) NOT NULL COMMENT '占比类型,1:游戏盈亏(投注-派彩),2:充值,3:取款，4:营收, 5:平台彩金, 6:平台服务(人工扣款)',
`proportion_value` varchar(10)  NULL DEFAULT NULL COMMENT '占比值百分比',
`settle_status` tinyint(3) NULL DEFAULT NULL COMMENT '参与结算,1:是,2:否',
`part_value` varchar(10) NULL DEFAULT NULL COMMENT '参与比例值',
`status` tinyint(3) NULL DEFAULT 1 COMMENT '状态,1:正常,2:停用',
`updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理盈亏返佣 成本设置';

#代理盈亏返佣平台数据统计
CREATE TABLE `agent_plat_earnlose`  (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`date` date NOT NULL DEFAULT '0000-00-00' COMMENT '统计日期',
`proportion` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总占成',
`bet_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '投注额',
`bet_amount_list` json NOT NULL COMMENT '游戏投注列表',
`lose_earn_list` json NOT NULL COMMENT '游戏盈亏列表',
`prize_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '派奖金额',
`lose_earn` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '盈亏',
`fee_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '总扣款(总成本)',
`revenue_amount` decimal(18, 2) NOT NULL COMMENT '总营收（总充值-总取款）',
`loseearn_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '盈亏占比金额',
`withdrawal_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '取款占比金额',
`deposit_ratio_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '充值占比金额',
`revenue_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '营收占比金额（输赢）',
`coupon_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠占比金额',
`manual_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '人口扣款占比金额',
`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE INDEX `udx_date`(`date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代理盈亏返佣平台数据统计';


#出入款报表
ALTER TABLE `rpt_deposit_withdrawal_day` ADD COLUMN `profit_loss_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '盈亏结算笔数' AFTER `shares_settle_amount`, ADD COLUMN `profit_loss_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '盈亏结算金额' AFTER `profit_loss_cnt`;

#用户盈亏
ALTER TABLE `user_agent` ADD COLUMN `profit_loss_value` varchar(200)  NULL DEFAULT NULL COMMENT '盈亏占成' AFTER `proportion_value`;

#增加注单数目
ALTER TABLE `orders_report` ADD COLUMN `num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注单数' AFTER `user_id`,ADD COLUMN `dml` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '打码量' AFTER `bet`;

#返佣结算
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`)
values
(302, 0, '盈亏返佣', NULL, NULL, NULL, 1),
(303,302,'盈亏返佣比例',null,null,null,1),
(304,303,'查询','GET','/profitloss/profitratio',1,1),
(305,303,'编辑','PUT','/profitloss/profitratio',2,1),
(306, 302, '盈亏成本设置', NULL, NULL, NULL, 1),
(307, 306, '查询', 'GET', '/profitloss/fee', NULL, 1),
(308, 306, '编辑', 'PUT', '/profitloss/fee', NULL, 1),
(309, 302, '盈亏设置', NULL, NULL, NULL, 1),
(310, 309, '查询', 'GET', '/profitloss/profitsettle', NULL, 1),
(311, 309, '编辑', 'PUT', '/profitloss/profitsettle', NULL, 1),
(312, 302, '盈亏报表', NULL, NULL, NULL, 1),
(313, 312, '查询', 'GET', '/report/agent/profit', NULL, 1);

#盈亏设置
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`)
VALUES
('rakeBack', '是否开启盈亏结算返佣', 'bool', 'bkge_open_loseearn', '0', '1开，0关', 'enabled', '2022-08-11 16:43:00'),
( 'profit_loss', '盈亏设置', 'string', 'proportion', NULL, NULL, 'enabled', NULL),
('profit_loss','盈亏返佣比例','string','profit_ratio','{"default":{"ratio":"100","is_take":"1"}}',null,'enabled',null),
('rakeBack','盈亏返佣方式','int','bkge_settle_type',3,'1日 2周 3月','enabled','2022-08-22 17:21:20');