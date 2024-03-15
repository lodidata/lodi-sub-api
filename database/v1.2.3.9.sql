#盈亏返佣结算配置
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('profit_loss', '盈亏每日返佣条件', 'string', 'daily_condition', '{\"recharge_min\":0,\"eff_user\":0,\"new_user\":0}', 'recharge_min最小累计充值，eff_user有效人数，new_user新增人数', 'enabled', '2022-11-08 11:26:26');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('profit_loss', '盈亏每周返佣条件', 'string', 'weekly_condition', '{\"recharge_min\":0,\"eff_user\":0,\"new_user\":0}', 'recharge_min最小累计充值，eff_user有效人数，new_user新增人数', 'enabled', '2022-11-08 11:26:26');
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('profit_loss', '盈亏每月返佣条件', 'string', 'monthly_condition', '{\"recharge_min\":0,\"eff_user\":0,\"new_user\":0}', 'recharge_min最小累计充值，eff_user有效人数，new_user新增人数', 'enabled', '2022-11-08 11:26:26');

#盈亏返佣
ALTER TABLE `funds` MODIFY COLUMN `share_balance` int(11) NOT NULL DEFAULT 0 COMMENT '股东分红余额';
ALTER TABLE `funds_deal_log`  MODIFY COLUMN `deal_money` int(11) NULL DEFAULT 0 COMMENT '交易金额';

ALTER TABLE `agent_loseearn_bkge` MODIFY COLUMN `settle_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '结算金额';
ALTER TABLE `agent_loseearn_month_bkge` MODIFY COLUMN `settle_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '结算金额';
ALTER TABLE `agent_loseearn_week_bkge` MODIFY COLUMN `settle_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '结算金额';
ALTER TABLE `agent_loseearn_bkge` ADD COLUMN `memo` varchar(30) NOT NULL DEFAULT '' COMMENT '备注（没有佣金的备注）' AFTER `fee_list`;
ALTER TABLE `agent_loseearn_week_bkge` ADD COLUMN `memo` varchar(30) NOT NULL DEFAULT '' COMMENT '备注（没有佣金的备注）' AFTER `fee_list`;
ALTER TABLE `agent_loseearn_month_bkge` ADD COLUMN `memo` varchar(30) NOT NULL DEFAULT '' COMMENT '备注（没有佣金的备注）' AFTER `fee_list`;

ALTER TABLE `rpt_user` ADD INDEX `idx_superior_date`(`superior_id`, `count_date`);

#菲版是否开启返佣计算自身
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('rakeBack', '返佣结算是否算本身', 'int', 'bkge_calculation_self', 1, '1开，0关', 'enabled', '2022-11-08 14:25:56');

#代理申请
alter table agent_apply add `uid_agent` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级代理uid' after user_id;
alter table agent_apply add `uid_agent_name` varchar(30) NOT NULL DEFAULT '' COMMENT '上级代理名称' after uid_agent;
alter table agent_apply add `deal_user` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '处理人员 1、后台处理 2、上级处理' after operate_uid;


#盈亏设置
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '1级默认占比开关', 'bool', 'default_proportion_switch', '0', '1开,0关', 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '1级固定占比开关', 'bool', 'fixed_proportion_switch', '0', '1开,0关', 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '下级固定占比开关', 'bool', 'sub_proportion_switch', '0', '1开,0关', 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '1级默认占比', 'string', 'default_proportion', NULL, NULL, 'enabled', NULL);
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('profit_loss', '1级固定占比', 'string', 'fixed_proportion', NULL, NULL, 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '下级固定占比', 'string', 'sub_fixed_proportion', NULL, NULL, 'enabled', NULL);

#修改下级默认占比
UPDATE `system_config` SET `value` = '{\"GAME\":\"0,90\",\"LIVE\":\"0,90\",\"SPORT\":\"0,90\",\"QP\":\"0,90\",\"ESPORTS\":\"0,90\",\"ARCADE\":\"0,90\",\"TABLE\":\"0,90\",\"BY\":\"0,90\",\"SABONG\":\"0,90\"}' WHERE `module`='profit_loss' and `key`='sub_default_proportion';



#安装包表
CREATE TABLE `app_package` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '安装包类型 1安卓 2苹果',
   `name` varchar(120) NOT NULL DEFAULT '' COMMENT '安装包名称',
   `version` varchar(120) NOT NULL DEFAULT '' COMMENT '版本',
   `bag_url` varchar(200) NOT NULL DEFAULT '' COMMENT '安装包地址',
   `icon_url` varchar(200) NOT NULL DEFAULT '' COMMENT '图标地址',
   `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 0正常，1停用',
   `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
   `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='安装包管理\r\n@author: stanley';