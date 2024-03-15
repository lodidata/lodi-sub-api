INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('rakeBack', '是否开启盈亏结算返佣', 'bool', 'bkge_open_loseearn', '0', '1开，0关', 'enabled', '2022-08-11 16:43:00');

//返佣结算
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(303,302,'盈亏返佣比例',null,null,null,1),(304,303,'查询','GET','/profitloss/profitratio',1,1),(305,303,'编辑','PUT','/profitloss/profitratio',2,1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (309, 302, '盈亏设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (310, 309, '查询', 'GET', '/profitloss/profitsettle', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (311, 309, '编辑', 'PUT', '/profitloss/profitsettle', NULL, 1);


insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`,`updated_at`) value('profit_loss','盈亏返佣比例','string','profit_ratio','{"default":{"ratio":"100","is_take":"1"}}',null,'enabled',null);
insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`,`updated_at`) values('rakeBack','盈亏返佣方式','int','bkge_settle_type',1,'1日 2周 3月','enabled','2022-08-22 17:21:20');

//成本设置菜单
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (306, 302, '盈亏成本设置', NULL, NULL, NULL, 1),(307, 306, '查询', 'GET', '/profitloss/fee', NULL, 1),(308, 306, '编辑', 'PUT', '/profitloss/fee', NULL, 1);

#盈亏设置
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'profit_loss', '盈亏设置', 'string', 'proportion', NULL, NULL, 'enabled', NULL);


#出入款报表
ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `profit_loss_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '盈亏结算笔数' AFTER `shares_settle_amount`,
ADD COLUMN `profit_loss_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '盈亏结算金额' AFTER `profit_loss_cnt`;

#用户盈亏
ALTER TABLE `user_agent`
ADD COLUMN `profit_loss_value` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '盈亏占成' AFTER `proportion_value`;

#增加注单数目
ALTER TABLE `orders_report`
ADD COLUMN `num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注单数' AFTER `user_id`;
ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注单数目' AFTER `agent_cnt`;

ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `bet_amount_list` json NOT NULL COMMENT '游戏投注列表' AFTER `loseearn_amount`;
ALTER TABLE `orders_report`
ADD COLUMN `dml` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '打码量' AFTER `bet`;
ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `dml_amount` decimal(18, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '打码量' AFTER `bet_amount`;

#盈亏报表数据
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (312, 146, '盈亏报表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (313, 312, '查询', 'GET', '/report/agent/profit', NULL, 1);