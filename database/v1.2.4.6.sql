INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '管理员pin密码开关', 'bool', 'status', 0, '开关，1:开，0：关', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '管理员pin密码重置开关', 'bool', 'reset_status', 0, '重置开关，1:开，0：关', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '管理员pin密码重置周期', 'int', 'reset_period', 1, '重置周期，1:周，2：月', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '主钱包-手动存款', 'int', 'manual_deposit', 0, '', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '主钱包-手动增加余额', 'int', 'add_money', 0, '', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '主钱包-增加可提余额', 'int', 'add_free_money', 0, '', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('admin_pin_password', '盈亏返佣-手动存款', 'int', 'add_share_balance', 0, '', 'enabled');

ALTER TABLE `admin_user`
ADD COLUMN `pin_password` char(32) NULL COMMENT 'pin密码' AFTER `salt`,
ADD COLUMN `pin_salt` char(6) NULL COMMENT 'pin盐' AFTER `pin_password`,
ADD COLUMN `reset_password` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重置密码（1：已重置，0：未重置）' AFTER `is_master`,
ADD COLUMN `reset_pin_time` timestamp NULL DEFAULT NULL COMMENT '重置pin密码的时间' AFTER `loginip`;

CREATE TABLE `funds_manual_check`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `username` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户名',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '提出类型:1.手动存款,2.手动提款,3.发放优惠,4,手动减少余额,5,手动增加余额,6.发放返水,7.子转主钱包,8 主转子钱包',
  `money` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '金额',
  `balance` int(10) NOT NULL DEFAULT 0 COMMENT '账户余额',
  `coupon` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优惠金额',
  `withdraw_bet` int(10) NOT NULL DEFAULT 0 COMMENT '打码量',
  `apply_admin_uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请的管理员',
  `admin_uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核的管理员id',
  `wallet_type` tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '钱包类型， 1 主钱包， 2 返佣钱包',
  `confirm_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '审核时间',
  `memo` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态   (0:待处理，1：同意，2：拒绝)',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_created`(`created`) USING BTREE,
  INDEX `idx_userid`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '大额加款列表' ROW_FORMAT = Dynamic;

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (416, 73, '大额加款', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (417, 416, '查询', 'GET', '/cash/manualcheck/records', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (418, 416, '同意/拒绝', 'POST', '/cash/manualcheck', NULL, 1);
