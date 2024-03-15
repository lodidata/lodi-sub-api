#新增数据表user_data_review
CREATE TABLE `user_data_review` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `update_content` varchar(255)  DEFAULT '' COMMENT '修改内容',
    `user_id` int(11) unsigned NOT NULL,
    `name` varchar(50)  DEFAULT NULL COMMENT '真实姓名',
    `account` varchar(100)  NOT NULL COMMENT '用户账号',
    `password` char(32)  DEFAULT '' COMMENT '登录密码',
    `pin_password` varchar(50)  DEFAULT '' COMMENT 'PIN密码',
    `bank_id` int(11) DEFAULT '0' COMMENT '银行id',
    `bank_account_name` varchar(50)  DEFAULT '' COMMENT '开户名',
    `bank_card` char(16)  DEFAULT '' COMMENT '银行卡号',
    `account_bank` varchar(100)  DEFAULT '' COMMENT '开户行',
    `status` tinyint(1) DEFAULT '0' COMMENT '审核状态 0： 审核中1 : 通过 2：拒绝 ',
    `updated` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `created_id` int(11) DEFAULT '0' COMMENT '发起人',
    `created` datetime NOT NULL COMMENT '创建时间',
    `operator_id` int(11) DEFAULT '0' COMMENT '操作人',
    `operator_name` varchar(255)  DEFAULT '' COMMENT '操作人名称',
    `remarks` text NOT NULL COMMENT '备注',
    `rejection_reason` text COMMENT '驳回原因',
    `salt` char(6) NOT NULL DEFAULT '' COMMENT '6位随机码',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_bank_id` (`bank_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COMMENT='审核用户资料表';
#渠道管理权限菜单
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
    VALUES (365, 42, '资料审核', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
    VALUES (366, 365, '资料审核列表', 'GET', '/user/review', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
    VALUES (367, 365, '新增', 'POST', '/user/review', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
    VALUES (368, 365, '资料审批', 'PATCH', '/user/review/examine', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`)
    VALUES (369, 365, '资料详情', 'GET', '/user/review/examine', NULL, 1);

#热门游戏优化
alter table game_menu add `hot_sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '热门顺序' after `sort`;
alter table game_menu add `hot_status` set('default','enabled','disabled') NOT NULL DEFAULT 'disabled' COMMENT '热门开关' after `status`;
alter table game_3th add `hot_sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '热门顺序' after `sort`;
alter table game_3th add `hot_status` set('default','enabled','disabled') NOT NULL DEFAULT 'disabled' COMMENT '热门开关' after `status`;
insert into game_menu(`pid`,`type`,`name`,`alias`,`rename`,`qp_img`,`qp_img2`,`img`,`list_mode`,`quit`,`sort`,`hot_sort`,`status`,`hot_status`,`update_at`,`switch`,`across_sort`,`across_status`) values(0,'HOT','All Game','HOT','热门游戏','','','',1,1,1,1,'disabled','disabled','2022-09-23 00:00:00','disabled',1,'disabled');

#系统设置
INSERT INTO `system_config` ( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at` )
VALUES( 'system', '活动进度条展示开关', 'bool', 'active_progress', '0', '1开0关', 'enabled', NULL );

#增加索引
ALTER TABLE `agent_loseearn_bkge`
ADD INDEX `idx_bkge_time`(`bkge_time`) USING BTREE;

ALTER TABLE `agent_loseearn_month_bkge`
ADD INDEX `idx_bkge_time`(`bkge_time`) USING BTREE;

ALTER TABLE `agent_loseearn_week_bkge`
ADD INDEX `idx_bkge_time`(`bkge_time`) USING BTREE;
