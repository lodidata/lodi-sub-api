#代理商占成设置
ALTER TABLE `user_agent`
ADD COLUMN `proportion_type` tinyint(3) unsigned DEFAULT NULL COMMENT '占成类型,1:固定,2:自动' AFTER `junior_bkge`,
ADD COLUMN `proportion_value` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '固定占成股' AFTER `proportion_type`;

#代理结算设置
CREATE TABLE `user_agent_settle` (
`id` tinyint(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
`name` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '项目名',
`type` tinyint(6) NOT NULL COMMENT '占比类型,1:流水,2:充值,3:营收,4:优惠彩金,5:人工扣款',
`game_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '游戏类型(game_menu里一级type)',
`proportion_value` varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '占比值百分比',
`settle_status` tinyint(3) DEFAULT NULL COMMENT '参与结算,1:是,2:否',
`part_value` varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '参与比例值',
`status` tinyint(3) DEFAULT '1' COMMENT '状态,1:正常,2:停用',
`create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代理盈利结算设置';

#代理商总股份
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`)
VALUES ( 'agent', '公司总股份', 'string', 'shares', '', NULL, 'enabled', NULL);

INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`)
VALUES ( 'agent', '占成设置', 'string', 'proportion', '', NULL, 'enabled', NULL);


#出入款报表
ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `shares_settle_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '股东结算笔数' AFTER `agent_first_deposit_num`,
ADD COLUMN `shares_settle_amount` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '股东结算金额' AFTER `shares_settle_cnt`;

#系统配置改变类型
ALTER TABLE `system_config`
MODIFY COLUMN `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `key`;


#菜单
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (293, 0, '股东管理', NULL, NULL, NULL, 1);


INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (280, 293, '代理盈利结算设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (281, 280, '查询', 'GET', '/system/settle', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (282, 280, '编辑结算', 'PUT', '/system/settle', NULL, 1);

INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (290, 293, '代理股份占成设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (291, 290, '查询', 'GET', '/system/shares', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (292, 290, '编辑', 'PUT', '/system/shares', NULL, 1);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (283, 293, '代理结算报表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (284, 283, '代理结算报表查询', 'GET', '/report/agent/shareholder', NULL, 1);

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (285, 123, '视频播放', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (286, 285, '视频播放查询', 'GET', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (287, 285, '视频播放新增', 'POST', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (288, 285, '视频播放修改', 'PUT', '/video', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (289, 285, '视频播放删除', 'DELETE', '/video', NULL, 1);


CREATE TABLE `agent_video_conf`  (
     `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
     `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '视频连接',
     `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '视频位置',
     `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '启用状态：1-启用，0-未启用',
     `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
     PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '无限代理-推广视频管理' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;