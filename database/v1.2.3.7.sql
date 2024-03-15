#游戏运营商报表
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (379, 146, '游戏运营商报表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (380, 379, '游戏运营商报表查询', 'GET', '/report/operation', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (381, 380, '游戏运营商报表详情', 'GET', '/report/operation/detail', NULL, 1);

#渠道访问记录
CREATE TABLE `user_channel_logs` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户UID',
 `channel_id` varchar(50) DEFAULT NULL COMMENT '渠道id',
 `log_ip` varchar(16) DEFAULT NULL COMMENT 'IP地址',
 `memo` varchar(255) DEFAULT NULL COMMENT '备注',
 `platform` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:pc, 2:h5, 3:ios,4:android',
 `domain` varchar(180) DEFAULT NULL COMMENT '域名',
 `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`) USING BTREE,
 KEY `idx_log_ip` (`log_ip`),
 KEY `idx_channel_id` (`channel_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='渠道推广访问记录表';

#彩金活动
ALTER TABLE `active_handsel` ADD COLUMN `uid_list` longtext NULL COMMENT '指定的uid列表' AFTER `recharge_coin`;