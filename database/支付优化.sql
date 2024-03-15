CREATE TABLE `pay_channel` (
`id` tinyint(11) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '名称',
`type` tinyint(10) NOT NULL DEFAULT '1',
`img` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '角标url',
`text` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '文本',
`sort` tinyint(11) NOT NULL DEFAULT '0' COMMENT '排序',
`status` tinyint(11) NOT NULL DEFAULT '0' COMMENT '状态 0:停用,1:启用',
`min_money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最小支付金额',
`max_money` int(10) unsigned NOT NULL COMMENT '最大支付金额',
`money_day_stop` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '日停用金额',
`money_stop` int(10) unsigned NOT NULL COMMENT '累计停用金额',
`rechage_money` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '快捷充值',
`give` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否赠送 0:否,1:是',
`give_protion` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '赠送百分比',
`give_dml` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '赠送打码量',
`remark` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '备注',
`create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='支付渠道表';


CREATE TABLE `payment_channel` (
`id` tinyint(11) unsigned NOT NULL AUTO_INCREMENT,
`pay_channel_id` int(11) unsigned NOT NULL COMMENT '渠道id',
`pay_config_id` int(11) unsigned NOT NULL COMMENT '支付id',
`name` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '名称',
`type` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '类型',
`img` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '角标url',
`logo` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'logo',
`text` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '文本',
`sort` tinyint(11) NOT NULL DEFAULT '0' COMMENT '排序',
`status` tinyint(11) NOT NULL DEFAULT '0' COMMENT '状态 0:停用,1:启用',
`min_money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最小支付金额',
`max_money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最大支付金额',
`money_day_stop` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '日停用金额',
`money_stop` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '累计停用金额',
`rechage_money` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '快捷充值',
`give` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否赠送 0:否,1:是',
`give_protion` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '赠送百分比',
`give_dml` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '赠送打码量',
`remark` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '备注',
`create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `idx_pay_channel_id` (`pay_channel_id`) USING BTREE,
KEY `idx_pay_config_id` (`pay_config_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='支付通道表';

ALTER TABLE `funds_deposit`
ADD COLUMN `payment_id` int(11) NULL DEFAULT NULL COMMENT '通道id' AFTER `receive_bank_info`;

#初始化
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (1, 'GCASH', 'GCASH', NULL, NULL, 1, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (2, 'Debit_card', 'Debit_card', NULL, NULL, 2, 1, 0, 0, 0, 0, '', 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (3, 'offline', 'localbank', NULL, NULL, 3, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (4, '711', '711_direct', NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (5, 'Grab', 'grabpay', NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (6, 'PayMaya', 'qr', NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (7, 'BPIA', 'BPIA', NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `pay_channel`( `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (8, 'UBPB', 'UBPB', NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);


insert into payment_channel (pay_channel_id,pay_config_id,`name`) select 1,id,`name` from pay_config;
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES (2, 2, 'AIPAY', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES ( 4, 11, '711', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES ( 5, 11, 'GRAB', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES ( 6, 11, 'QR', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES ( 7, 11, 'BPIA', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) VALUES ( 8, 11, 'UBPB', NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);
