ALTER TABLE `ip_black`
MODIFY COLUMN `ip` varbinary(16) NOT NULL COMMENT '限制ip' AFTER `id`,
MODIFY COLUMN `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `memo`,
MODIFY COLUMN `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created`,
MODIFY COLUMN `memo` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '备注' AFTER `status`,
CHANGE COLUMN `status` `operator` varchar(20) NOT NULL DEFAULT '' COMMENT '操作者' AFTER `intercept`,
CHANGE COLUMN `intercept` `accounts_num` mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '冻结账号数' AFTER `ip`,
ADD COLUMN `valid_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '有效时间 (超过时间就解封)' AFTER `memo`,
ADD INDEX `idx_valid_time`(`valid_time`) USING BTREE,
ADD INDEX `idx_created`(`created`) USING BTREE;

INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('network_safty', 'IP冻结设置', 'bool', 'check_black_ip', '0', '1开，0关', 'enabled');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ('network_safty', '冻结时间', 'int', 'lock_time', 1, '默认一天(数字代表天数，0表示永久)', 'enabled');

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (419, 146, 'IP冻结报表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (420, 419, '查询', 'GET', '/ip/black', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (421, 419, '新增', 'POST', '/ip/black', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (422, 419, '解除冻结', 'DELETE', '/ip/black', NULL, 1);
