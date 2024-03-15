ALTER TABLE `funds_deal_manual`
MODIFY COLUMN `user_id` int(11) UNSIGNED NOT NULL COMMENT '用户id' AFTER `id`;

ALTER TABLE `rpt_user`
MODIFY COLUMN `withdrawal_user_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '用戶取款金额' AFTER `deposit_user_amount`,
ADD COLUMN `manual_deduction_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '人工扣款金额' AFTER `withdrawal_user_amount`;

ALTER TABLE `rpt_agent`
ADD COLUMN `manual_deduction_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '代理下级团队人工扣款金额' AFTER `withdrawal_agent_amount`;

-- ----------------------------
-- Table structure for orders_report_exec
-- ----------------------------
DROP TABLE IF EXISTS `orders_report_exec`;
CREATE TABLE `orders_report_exec`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `last_order_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上次处理到的orders表id',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = 'orders_report 记录统计到哪个id了' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of orders_report_exec
-- ----------------------------
INSERT INTO `orders_report_exec` VALUES (1, 0, '2022-07-06 14:23:37', '2022-06-25 09:43:11');

-- ----------------------------
-- Table structure for orders_report
-- ----------------------------
DROP TABLE IF EXISTS `orders_report`;
CREATE TABLE `orders_report`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NULL DEFAULT '0000-00-00',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `bet` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '下注量',
  `bet_amount_list` json NOT NULL COMMENT '游戏投注列表',
  `lose_earn_list` json NOT NULL COMMENT '游戏输赢列表',
  `send_money` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '派奖金额',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `udx_userid_date`(`user_id`, `date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '所有订单数据报表' ROW_FORMAT = DYNAMIC;


DROP TABLE IF EXISTS `plat_earnlose`;
CREATE TABLE `plat_earnlose`  (
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
  `bet_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '流水占比金额',
  `deposit_ratio_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '充值占比金额',
  `revenue_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '营收占比金额（输赢）',
  `coupon_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠占比金额',
  `manual_ratio_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '人口扣款占比金额',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `udx_date`(`date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '平台输赢' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for unlimited_agent_bkge
-- ----------------------------
DROP TABLE IF EXISTS `unlimited_agent_bkge`;
CREATE TABLE `unlimited_agent_bkge`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `deal_log_no` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '交易流水号(funds_deal_log里的order_number)',
  `date` date NOT NULL DEFAULT '0000-00-00' COMMENT '日期(格式 2022-03-04)',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `user_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名称',
  `agent_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '上级代理名称',
  `agent_cnt` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下级代理人数(与rpt_agent里的一样)',
  `bkge` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '返佣金额',
  `settle_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '结算金额',
  `fee_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '扣款金额',
  `bet_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '投注额(总流水)',
  `bet_amount_list` json NOT NULL COMMENT '游戏投注列表',
  `bkge_list` json NOT NULL COMMENT '游戏返佣列表',
  `proportion_list` json NOT NULL COMMENT '游戏占成列表',
  `fee_list` json NOT NULL COMMENT '扣款列表',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1：已发放，0：未发放',
  `bkge_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '返佣时间',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `udx_date_user_id`(`date`, `user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '无限代理返佣表' ROW_FORMAT = Dynamic;

INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('rakeBack', '是否开启全民股东返佣', 'bool', 'bkge_open_unlimited', '0', '1开，0关', 'enabled', '2022-07-05 16:43:00');

#AIpay支付SQL
UPDATE pay_config SET `pay_type` = '[{"name":"QR","key":"1"},{"name":"TM","key":"2"}]' WHERE `type` = 'aipay';




#代理商占成设置
ALTER TABLE `user_agent`
    ADD COLUMN `proportion_type` tinyint(3) unsigned DEFAULT NULL COMMENT '占成类型,1:固定,2:自动' AFTER `junior_bkge`,
ADD COLUMN `proportion_value` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '固定占成股' AFTER `proportion_type`;

#代理结算设置
DROP TABLE IF EXISTS `user_agent_settle`;
CREATE TABLE `user_agent_settle` (
 `id` tinyint(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
 `name` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '项目名',
 `type` tinyint(6) NOT NULL COMMENT '占比类型,1:流水,2:充值,3:营收,4:优惠彩金,5:人工扣款',
 `game_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '游戏类型(game_menu里一级type)',
 `proportion_value` varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '占比值百分比',
 `settle_status` tinyint(3) DEFAULT NULL COMMENT '参与结算,1:是,2:否',
 `part_value` varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '参与比例值',
 `status` tinyint(3) DEFAULT '1' COMMENT '状态,1:正常,2:停用',
 `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
 `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代理盈利结算设置';

#代理商总股份
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`)VALUES ( 'agent', '公司总股份', 'string', 'shares', '', NULL, 'enabled', NULL);

INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`)VALUES ( 'agent', '占成设置', 'string', 'proportion', '', NULL, 'enabled', NULL);


#出入款报表
ALTER TABLE `rpt_deposit_withdrawal_day`
    ADD COLUMN `shares_settle_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '股东结算笔数' AFTER `agent_first_deposit_num`,
ADD COLUMN `shares_settle_amount` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '股东结算金额' AFTER `shares_settle_cnt`;

#系统配置改变类型
ALTER TABLE `system_config` MODIFY COLUMN `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `key`;


#菜单
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (293, 0, '股东管理', NULL, NULL, NULL, 1);


INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (280, 293, '代理盈利结算设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (281, 280, '查询', 'GET', '/system/settle', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (282, 280, '编辑结算', 'PUT', '/system/settle', NULL, 1);

INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (290, 293, '代理股份占成设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (291, 290, '查询', 'GET', '/system/shares', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (292, 290, '编辑', 'PUT', '/system/shares', NULL, 1);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (283, 293, '代理结算报表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (284, 283, '代理结算报表查询', 'GET', '/report/agent/shareholder', NULL, 1);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (285, 123, '视频播放', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (286, 285, '视频播放查询', 'GET', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (287, 285, '视频播放新增', 'POST', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (288, 285, '视频播放修改', 'PUT', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (289, 285, '视频播放删除', 'DELETE', '/video', NULL, 1);

insert into admin_user_role_auth(`id`,`pid`,`name`,`status`) values(274,123,'社群论坛管理',1);
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`status`) values(275,274,'查询列表','GET','/community/list',1),(276,274,'添加社区','POST','/community/add',1),(277,274,'查询单个社区','GET','/community/edit',1),(278,274,'编辑社区','PUT','/community/edit',1),(279,274,'删除社区','POST','/community/del',1);
insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`,`updated_at`) values('system','社区论坛总开关','bool','community_bbs','1','1开，0关','enabled','2022-05-31 15:34:13');


CREATE TABLE `agent_video_conf`  (
                                     `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                     `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
                                     `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '视频连接',
                                     `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '视频位置',
                                     `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '启用状态：1-启用，0-未启用',
                                     `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
                                     PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '无限代理-推广视频管理' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;