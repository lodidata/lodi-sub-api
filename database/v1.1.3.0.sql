#消息通知表-新增彩金消息类型相关字段
ALTER TABLE `message`
ADD COLUMN `active_type` int NOT NULL DEFAULT 0 COMMENT '活动消息类型：12-彩金活动消息' AFTER `updated`,
ADD COLUMN `active_id` int NOT NULL DEFAULT 0 COMMENT '消息对应的活动id' AFTER `active_type`;

#彩金活动表添加字段
ALTER TABLE `active_handsel`
ADD COLUMN `limit_game` varchar(255) NOT NULL DEFAULT '' COMMENT '指定的游戏分类，多个游戏id之间逗号隔开' AFTER `create_time`,
ADD COLUMN `recharge_num` int NOT NULL DEFAULT 0 COMMENT '充值金额' AFTER `limit_game`,
ADD COLUMN `valid_time` datetime NOT NULL DEFAULT "1970-01-01 00:00:00" COMMENT '有效时间' AFTER `recharge_num`;

#彩金发放记录表添加字段
ALTER TABLE `active_handsel_log`
ADD COLUMN `receive_num` int NOT NULL DEFAULT 0 COMMENT '已经领取的人数' AFTER `give_num`,
ADD COLUMN `valid_time` datetime NOT NULL DEFAULT "1970-01-01 00:00:00" COMMENT '有效时间' AFTER `give_time`;

#彩金领取记录表
CREATE TABLE `handsel_receive_log`  (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
`user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
`handsel_log_id` int(11) NOT NULL DEFAULT 0 COMMENT '对应active_handsel_log表id',
`status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '领取状态：0-未领取，1-已领取，2-彩金失效',
`receive_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '领取时间',
`create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
`limit_game` varchar(255) NOT NULL DEFAULT '' COMMENT '指定游戏分类',
`recharge_num` int(11) NOT NULL DEFAULT 0 COMMENT '充值金额',
`valid_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '有效时间',
`receive_amount` int(11) NOT NULL DEFAULT 0 COMMENT '领取的彩金额',
`dm_num` int(11) NOT NULL DEFAULT 0 COMMENT '打码量',
PRIMARY KEY (`id`) USING BTREE,
INDEX `user_id`(`user_id`) USING BTREE,
INDEX `handsel_log_id`(`handsel_log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = '用户领取彩金活动记录';

#客服管理

CREATE TABLE `kefu_telecom` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`kefu_id` int(11) NOT NULL DEFAULT 0 COMMENT '客服ID',
`name` varchar(40) NOT NULL DEFAULT '' COMMENT '客服名称',
`roll_num` int(11) NOT NULL DEFAULT 0 COMMENT '名单总数',
`not_register` int(11) NOT NULL DEFAULT 0 COMMENT '未注册人数',
`register_num` int(11) NOT NULL DEFAULT 0 COMMENT '注册人数',
`recharge_num` int(11) NOT NULL DEFAULT 0 COMMENT '充值人数',
`recharge_amount` bigint(20) UNSIGNED NULL DEFAULT 0 COMMENT '充值金额',
`recharge_mean` bigint(20) UNSIGNED NULL DEFAULT 0 COMMENT '平均充值金额',
`created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_created` (`created`) USING BTREE
) ENGINE=InnoDB  AUTO_INCREMENT = 1 CHARSET=utf8mb4  COMMENT='客服电访表';


CREATE TABLE `kefu_telecom_item` (
 `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
 `pid` int(11) NOT NULL DEFAULT 0 COMMENT '电访表ID',
 `user_id` int(11) NOT NULL COMMENT 'user表中的id,用户ID',
 `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号',
 `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
 `register_time` timestamp NULL DEFAULT NULL COMMENT '注册时间',
 `recharge_time` timestamp NULL DEFAULT NULL COMMENT '首充时间',
 `recharge_amount` bigint(20) UNSIGNED NULL DEFAULT 0 COMMENT '首充金额',
 `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`) USING BTREE,
 UNIQUE KEY `idx_pid_mobile` (`pid`,`mobile`) USING BTREE,
 KEY `idx_created` (`created`) USING BTREE
) ENGINE=InnoDB  AUTO_INCREMENT = 1 CHARSET=utf8mb4  COMMENT='客服电访明细表';


CREATE TABLE `kefu_user` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(40) NOT NULL COMMENT '客服名称',
 `put_time` timestamp NULL DEFAULT NULL COMMENT '最近导入时间',
 `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  AUTO_INCREMENT = 1 CHARSET=utf8mb4  COMMENT='客服人员表';


#现金管理-批量赠送彩金
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
VALUES
(315,0,'客服管理',null,null,null,1),
(316,315,'电访后台',null,null,null,1),
(317,316,'查询','GET','/kefu/telecom',1,1),
(318,316,'编辑客服','POST','/kefu/telecom/export',2,1),
(319, 73, '批量赠送彩金', NULL, NULL, NULL, 1),
(320, 319, '彩金活动列表', 'GET', '/active/winnings/', NULL, 1),
(321, 320, '创建彩金活动', 'POST', '/active/winnings/', NULL, 1),
(322, 321, '编辑彩金活动', 'PUT', '/active/winnings/', NULL, 1),
(323, 322, '启用或禁用彩金活动', 'PATCH', '/active/winnings/', NULL, 1),
(324, 323, '删除彩金活动', 'DELETE', '/active/winnings/', NULL, 1),
(325, 43, '会员管理-导出', 'GET', '/user/list/export', NULL, 1),
(326, 74, '人工存提记录-导出', 'GET', '/cash/manual/records/export', NULL, 1),
(327, 86, '线上充值-导出', 'GET', '/cash/deposit/onlines/export', NULL, 1),
(328, 86, '线下转账-导出', 'GET', '/cash/deposit/offlines/export', NULL, 1),
(329, 89, '提现审核-导出', 'GET', '/cash/newwithdraw/export', NULL, 1),
(330, 95, '转账记录-导出', 'GET', '/thirdAdvance/transfer/export', NULL, 1),
(331, 100, '交易流水-导出', 'GET', '/funds/flow/export', NULL, 1) ,
(332, 104, '余额转换-导出', 'GET', '/cash/record/transfer/export', NULL, 1),
(333, 297, '批量赠送彩金-导出', 'GET', '/report/handsel/export', NULL, 1),
(334, 283, '股东会员报表-导出 ', 'GET', '/report/agent/shareholder/export', NULL, 1);

#报表管理-代理报表-导出
UPDATE `admin_user_role_auth` SET `name` = '代理报表-导出' WHERE `id` = 261;
#报表管理-会员报表-导出
UPDATE `admin_user_role_auth` SET `name` = '会员报表-导出' WHERE `id` = 260;
#报表管理-充值留存率详情-导出
UPDATE `admin_user_role_auth` SET `name` = '充值留存率详情-导出' WHERE `id` = 271;
#报表管理-活跃留存率详情-导出
UPDATE `admin_user_role_auth` SET `name` = '活跃留存率详情-导出' WHERE `id` = 273;