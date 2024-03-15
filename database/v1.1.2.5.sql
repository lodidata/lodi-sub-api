#系统设置sql
update system_config set `value`=0 where `key`='register_limit_ip_switch';

INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('system', '注册IP限制人数', 'int', 'register_limit_ip_count', '', NULL, 'enabled', now());


#普通用户占成设置
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'agent', '普通用户占成设置', 'string', 'user_proportion', '', NULL, 'enabled', NULL);

#ip设置菜单
DELETE FROM `admin_user_role_auth` WHERE `id` in (294,295,296);


ALTER TABLE `rpt_user` ADD COLUMN `dml` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '用户打码量' AFTER `bet_user_amount`;

ALTER TABLE `admin_index_third`
    ADD COLUMN `new_deposit_retention` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '首充次日付费留存' AFTER `recharge_first_avg`,
    ADD COLUMN `new_deposit_bet_retention` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '首充次日活跃留存' AFTER `new_deposit_retention`,
    ADD COLUMN `no_agent_user_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主渠道新增注册(注册时无上级代理)' AFTER `new_register_withdraw_amount`,
    ADD COLUMN `deposit_user_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总充值人数' AFTER `no_agent_user_num`,
    ADD COLUMN `bet_today_kill_rate` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '流水杀率' AFTER `deposit_user_num`,
    ADD COLUMN `revenue_today_kill_rate` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '营收杀率' AFTER `bet_today_kill_rate`,
    ADD COLUMN `old_user_deposit_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '老用户充值人数' AFTER `revenue_today_kill_rate`,
    ADD COLUMN `old_user_deposit_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '老用户充值金额' AFTER `old_user_deposit_num`,
    ADD COLUMN `old_user_deposit_avg` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '老用户平均充值金额' AFTER `old_user_deposit_amount`,
    ADD COLUMN `new_deposit_user_dml` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '新充打码量' AFTER `old_user_deposit_avg`,
    CHANGE COLUMN `witchdraw_total` `withdraw_total` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '总兑换' AFTER `recharge_total`;


insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`)
values
('user_agent','自身流水','bool','bet_amount','1','1开，0关','enabled'),
('user_agent','下级流水','bool','next_bet_amount','1','1开，0关','enabled'),
('user_agent','总流水','bool','total_bet_amount','1','1开，0关','enabled'),
('user_agent','注册用户','bool','new_register','1','1开，0关','enabled'),
('user_agent','下级人数','bool','next_agent','1','1开，0关','enabled'),
('user_agent','总充值人数','bool','recharge_user','1','1开，0关','enabled'),
('user_agent','总充值金额','bool','recharge_amount','1','1开，0关','enabled'),
('user_agent','盈亏金额','bool','profits','1','1开，0关','enabled'),
('admin_agent','自身流水','bool','bet_amount','1','1开，0关','enabled'),
('admin_agent','下级流水','bool','next_bet_amount','1','1开，0关','enabled'),
('admin_agent','总流水','bool','total_bet_amount','1','1开，0关','enabled'),
('admin_agent','股东分红','bool','profits','1','1开，0关','enabled'),
('admin_agent','股份','bool','proportion','1','1开，0关','enabled'),
('admin_agent','公司成本','bool','fee_amount','1','1开，0关','enabled'),
('admin_agent','新注册用户','bool','new_register','1','1开，0关','enabled'),
('admin_agent','首充人数','bool','first_recharge_user','1','1开，0关','enabled'),
('admin_agent','首存金额','bool','first_recharge_amount','1','1开，0关','enabled'),
('admin_agent','总充值金额','bool','all_recharge_amount','1','1开，0关','enabled'),
('admin_agent','有效用户','bool','valid_user','1','1开，0关','enabled'),
('admin_agent','有效投注','bool','valid_amount','1','1开，0关','enabled'),
('admin_agent','投注人数','bool','bet_user','1','1开，0关','enabled'),
('system', '注册IP限制人数', 'int', 'register_limit_ip_count', '', NULL, 'enabled');


INSERT INTO `active_type` (`id`, `name`, `description`, `sort`, `created`, `updated`) VALUES (12, 'give away bonus', 'give away bonus', 12, '2022-07-15 17:10:25', '2022-07-15 17:10:29');
INSERT INTO `active_template` (`id`, `name`, `description`, `state`, `created_uid`, `created`, `updated`) VALUES (12, 'give away bonus', 'give away bonus', '', NULL, '2022-07-15 17:18:16', '2022-07-15 17:18:19');

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
VALUES
(297, 146, '批量赠送彩金', NULL, NULL, NULL, 1),
(298, 297, '批量赠送彩金查询', 'GET', '/report/handsel', NULL, 1),
(299, 293, '股东实时数据开关', NULL, NULL, NULL, 1),
(300, 299, '查询', 'GET', '/system/agentswitch', 1, 1),
(301, 299, '编辑', 'PUT', '/system/agentswitch', 2, 1);
#彩金活动表
CREATE TABLE `active_handsel`  (
                                   `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                   `active_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动id，对应active表的id',
                                   `msg_title` varchar(100) NOT NULL DEFAULT '' COMMENT '消息标题',
                                   `msg_content` varchar(255) NOT NULL DEFAULT '' COMMENT '消息内容',
                                   `give_away` tinyint(4) NOT NULL DEFAULT 0 COMMENT '赠送条件：0-未知不赠送，1-指定用户，2-指定等级，3-批量赠送',
                                   `phone_list` longtext NOT NULL COMMENT '指定用户列表，多个手机号之间英文逗号隔开',
                                   `user_level` longtext NOT NULL COMMENT '指定等级列表，多个等级之间英文逗号隔开',
                                   `batch` longtext NOT NULL COMMENT '批量赠送列表，多个手机号之间英文逗号隔开',
                                   `batch_url` varchar(255) NOT NULL DEFAULT '' COMMENT '批量赠送时，上传的文件地址',
                                   `give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '赠送彩金数量',
                                   `dm_num` int(11) NOT NULL DEFAULT 0 COMMENT '打码量',
                                   `notice_type` varchar(50) NOT NULL COMMENT '通知类型：1-短信，2-邮箱，3站内信息通知，多种方式时编号之间用逗号隔开',
                                   `give_amount_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '赠送彩金时间',
                                   `is_now_give` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否立即赠送彩金：1-是，0-否',
                                   `state` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否已经赠送彩金：1-是，0-否',
                                   `create_time` datetime NOT NULL DEFAULT '1997-01-01 00:00:00' COMMENT '创建时间',
                                   PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COMMENT = '批量赠送彩金活动内容';

#彩金发放记录
CREATE TABLE `active_handsel_log`  (
                                       `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                       `active_handsel_id` int(11) NOT NULL DEFAULT 0 COMMENT '批量赠送彩金活动规则的id',
                                       `msg_title` varchar(100) NOT NULL DEFAULT '' COMMENT '消息标题',
                                       `msg_content` varchar(255) NOT NULL DEFAULT '' COMMENT '消息内容',
                                       `give_away` varchar(50) NOT NULL DEFAULT '' COMMENT '赠送方式',
                                       `notice_away` varchar(50) NOT NULL DEFAULT '' COMMENT '通知方式',
                                       `give_num` int(11) NOT NULL DEFAULT 0 COMMENT '赠送人数',
                                       `give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '赠送的彩金数',
                                       `dm_num` int(11) NOT NULL DEFAULT 0 COMMENT '打码量',
                                       `total_give_amount` int(11) NOT NULL DEFAULT 0 COMMENT '总赠送彩金数量',
                                       `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
                                       `give_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '赠送时间',
                                       PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = '批量赠送彩金发放记录';