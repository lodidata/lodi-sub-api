#活跃人数
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'profit_loss', '活跃人数', 'int', 'active_number', '0', NULL, 'enabled'),( 'profit_loss', '下级默认占比百分比', 'string', 'sub_default_proportion', NULL, NULL, 'enabled', NULL );

#默认盈亏占成
ALTER TABLE `user_agent`
ADD COLUMN `default_profit_loss_value` varchar(200)  NULL DEFAULT NULL COMMENT '默认盈亏占成' AFTER `profit_loss_value`;

#电访删除功能
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(342,316,'删除','DELETE','/kefu/telecom',3,1);

#luckypay增加支付
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES( 'recharge_type', 'QR', 'bool', 'qr', '1', '1开0关', 'enabled'),( 'recharge_type', 'UBPB', 'bool', 'ubpb', '1', '1开0关', 'enabled'),( 'recharge_type', 'BPIA', 'bool', 'bpia', '1', '1开0关', 'enabled');

#代理申请表
CREATE TABLE `agent_apply` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
   `contact_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '联系类型  1手机 2lien 3邮箱 4其他',
   `contact_value` varchar(255) NOT NULL DEFAULT '' COMMENT '联系内容',
   `reason` text COMMENT '申请理由',
   `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1拒绝 2通过',
   `operate_time` timestamp NULL DEFAULT NULL COMMENT '操作时间',
   `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
   `operate_uid` int(11) DEFAULT NULL COMMENT '操作者',
   `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`) USING BTREE,
   KEY `user_id` (`user_id`) USING BTREE,
   KEY `created` (`created`) USING BTREE
) ENGINE=InnoDB CHARSET=utf8mb4 COMMENT='代理申请表';

#代理审核后台
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) values(359,42,'代理申请',null,null,null,1),(360,359,'查询','GET','user/agent/apply',1,1),(361,359,'编辑','PUT','user/agent/apply',2,1);

#渠道管理优化
ALTER TABLE channel_management CHANGE `url` `url` TEXT COMMENT '渠道地址';

#user表添加索引
ALTER TABLE `user` ADD INDEX `idx_first_rech`(`first_recharge_time`) USING BTREE;

#渠道管理-充值留存率
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (356, 343, '渠道充值留存', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (357, 356, '获取报表', 'GET', '/channel/retention/recharge', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (358, 356, '导出报表', 'GET', '/channel/retention/recharge/export', NULL, 1);

#渠道管理-活跃留存
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (362, 343, '渠道活跃留存', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (363, 362, '获取报表', 'GET', '/channel/retention/active', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (364, 362, '导出报表', 'GET', '/channel/retention/active/export', NULL, 1);

#日盈亏报表增加活跃用户数
ALTER TABLE `agent_loseearn_bkge` ADD COLUMN `active_user_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下级活跃用户数(有充值和投注)';