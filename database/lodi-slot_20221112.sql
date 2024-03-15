CREATE TABLE `pay_channel` (
`id` tinyint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NULL DEFAULT NULL COMMENT '名称',
  `type` varchar(100) NULL DEFAULT NULL COMMENT '类型',
  `img` varchar(100) NULL DEFAULT NULL COMMENT '角标url',
  `logo` varchar(100) NULL DEFAULT NULL COMMENT 'logo',
  `text` varchar(100) NULL DEFAULT NULL COMMENT '文本',
  `sort` tinyint(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(11) NOT NULL DEFAULT 0 COMMENT '状态 0:停用,1:启用',
  `min_money` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最小支付金额',
  `max_money` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最大支付金额',
  `money_day_stop` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '日停用金额',
  `money_stop` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计停用金额',
  `rechage_money` varchar(255) NULL DEFAULT NULL COMMENT '快捷充值',
  `give` tinyint(10) NOT NULL DEFAULT 0 COMMENT '是否赠送 0:否,1:是',
  `give_protion` varchar(50) NULL DEFAULT NULL COMMENT '赠送百分比',
  `give_dml` varchar(50) NULL DEFAULT NULL COMMENT '赠送打码量',
  `remark` varchar(100) NULL DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_type`(`type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = '支付渠道表';


CREATE TABLE `payment_channel` (
 `id` tinyint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pay_channel_id` int(11) UNSIGNED NOT NULL COMMENT '渠道id',
  `pay_config_id` int(11) UNSIGNED NOT NULL COMMENT '支付id',
  `name` varchar(100) NULL DEFAULT NULL COMMENT '名称',
  `type` varchar(100) NULL DEFAULT NULL COMMENT '类型',
  `img` varchar(100) NULL DEFAULT NULL COMMENT '角标url',
  `logo` varchar(100) NULL DEFAULT NULL COMMENT 'logo',
  `text` varchar(100) NULL DEFAULT NULL COMMENT '文本',
  `sort` tinyint(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(11) NOT NULL DEFAULT 0 COMMENT '状态 0:停用,1:启用',
  `min_money` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最小支付金额',
  `max_money` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最大支付金额',
  `money_day_stop` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '日停用金额',
  `money_stop` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计停用金额',
  `rechage_money` varchar(255) NULL DEFAULT NULL COMMENT '快捷充值',
  `give` tinyint(10) NOT NULL DEFAULT 0 COMMENT '是否赠送 0:否,1:是',
  `give_protion` varchar(50) NULL DEFAULT NULL COMMENT '赠送百分比',
  `give_dml` varchar(50) NULL DEFAULT NULL COMMENT '赠送打码量',
  `remark` varchar(100) NULL DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pay_channel_id`(`pay_channel_id`) USING BTREE,
  INDEX `idx_pay_config_id`(`pay_config_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = '支付通道表';



ALTER TABLE `funds_deposit`
ADD COLUMN `payment_id` int(11) NULL DEFAULT NULL COMMENT '通道id' AFTER `receive_bank_info`;

#初始化
INSERT INTO `pay_channel`(`id`, `name`, `type`, `img`, `text`, `sort`, `status`, `min_money`, `max_money`, `money_day_stop`, `money_stop`, `rechage_money`, `give`, `give_protion`, `give_dml`, `remark`) 
VALUES 
(1, 'GCASH', 'GCASH', NULL, NULL, 1, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(2, 'Debit_card', 'Debit_card', NULL, NULL, 2, 1, 0, 0, 0, 0, '', 0, NULL, NULL, NULL),
(3, 'offline', 'localbank', NULL, NULL, 3, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(4, '711', '711_direct', NULL, NULL, 0, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(5, 'Grab', 'grabpay', NULL, NULL, 0, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(6, 'PayMaya', 'qr', NULL, NULL, 0, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(7, 'BPIA', 'BPIA', NULL, NULL, 0, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL),
(8, 'UBPB', 'UBPB', NULL, NULL, 0, 1, 0, 0, 0, 0, NULL, 0, NULL, NULL, NULL);


insert into payment_channel (pay_channel_id,pay_config_id,`name`) select 1,id,`name` from pay_config;




#---------------------先查是否有luckypay，如果没有不执行插入数据    

select id,`type`,`name` from pay_config where type='luckypay';

#---------------------[注意] 11为luckypay ID 




INSERT INTO `payment_channel`( `pay_channel_id`, `pay_config_id`, `name`) 
VALUES 
(2, 11, 'AIPAY'),
( 4, 11, '711'),
( 5, 11, 'GRAB'),
( 6, 11, 'QR'),
( 7, 11, 'BPIA'),
( 8, 11, 'UBPB');

update payment_channel set type = '711_direct' where pay_config_id=11 and name='711';
update payment_channel set type = 'grabpay' where pay_config_id=11 and name='GRAB';
update payment_channel set type = 'BPIA' where pay_config_id=11 and name='BPIA';
update payment_channel set type = 'qr' where pay_config_id=11 and name='QR';
update payment_channel set type = 'UBPB' where pay_config_id=11 and name='UBPB';

#更新渠道logo【注意图片域名】
update pay_channel set logo=CONCAT('https://img.caacaya.com/lodi/pay/',type,'.png');