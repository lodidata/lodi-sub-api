INSERT INTO `system_config`(`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (208, 'website', '落地页配置', 'json', 'landing_page_config', '{\"img\":\"\",\"jump_url\":\"\"}', '', 'enabled', '2022-12-08 17:09:55');
INSERT INTO `system_config`(`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (209, 'website', '代理说明配置', 'json', 'agent_desc_config', '{\"img\":\"\"}', NULL, 'enabled', '2022-12-08 17:10:14');
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`) VALUES (390, 123, '落地页配置');
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`) VALUES (391, 123, '代理说明配置');
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (392, 390, '落地页信息', 'GET', '/system/landingpage', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (393, 390, '落地页设置', 'PUT', '/system/landingpage', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (394, 391, '代理说明信息', 'GET', '/system/agentdesc', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (395, 391, '代理说明设置', 'PUT', '/system/agentdesc', NULL, 1);

INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (396, 74, '手动发放优惠-增加彩金', 'POST', '/cash/manual/coupon', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (397, 74, '股东分红-增加可提余额', 'POST', '/cash/manual/share/increase', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (398, 74, '股东分红-减少可提余额', 'POST', '/cash/manual/share/decrease', NULL, 1);

#银行卡提现限额
insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`,`updated_at`) values('withdraw','银行卡提现限额','json','withdraw_card_money','{\"withdraw_min\":100,\"withdraw_max\":50000}','json值','enabled','2022-12-22 15:53:00');



ALTER TABLE `pay_channel`
ADD COLUMN `guide_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '引导教程图片' AFTER `give_lottery_dml`;


CREATE TABLE `level_payment` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`level_id` int(11) unsigned NOT NULL COMMENT '等级id',
`payment_id` int(11) unsigned NOT NULL COMMENT '通道id',
`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE KEY `UNIQUE_ID` (`level_id`,`payment_id`) USING BTREE,
KEY `IDX_LEVEL_ID` (`level_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='支付通道等级配置表';

CREATE TABLE `level_bank_account` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`level_id` int(11) unsigned NOT NULL COMMENT '等级id',
`bank_account_id` int(11) unsigned NOT NULL COMMENT 'bank_accnount表主键id',
`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE KEY `UNIQUE_BANK` (`level_id`,`bank_account_id`) USING BTREE,
KEY `IDX_LEVEL_ACCOUNT` (`level_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='收款账号等级配置表';



insert into level_payment (level_id,payment_id) SELECT t1.id AS level_id, t2.id AS payment_id FROM user_level t1 JOIN payment_channel t2;
insert into level_bank_account(level_id,bank_account_id) SELECT t1.id AS level_id, t2.id AS bank_account_id FROM user_level t1 JOIN bank_account t2;

