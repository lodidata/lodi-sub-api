INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'website', '落地页配置', 'json', 'landing_page_config', '{\"img\":\"\",\"jump_url\":\"\",\"video\":\"\"}', '', 'enabled', '2022-12-08 17:09:55');
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'website', '代理说明配置', 'json', 'agent_desc_config', '{\"img\":\"\"}', NULL, 'enabled', '2022-12-08 17:10:14');
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'website', 'app落地页引导', 'json', 'app_boot', '{\"type\":2,\"top_img\":\"\",\"live_url\":\"\",\"download_img\":\"\"}', NULL, 'enabled');
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'website', 'app落地页风格图片地址', 'string', 'app_boot_style_img', '{\"one\":\"\",\"two\":\"\"}', NULL, 'enabled', '2023-01-04 11:42:56' );

INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (413, 123, '顶部漂浮下载', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (414, 413, '顶部漂浮下载查询', 'GET', '/copywriter/topFloat', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (415, 413, '顶部漂浮下载编辑/新增', 'PUT', '/copywriter/topFloat', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (390, 123, '落地页配置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (392, 390, '落地页信息', 'GET', '/system/landingpage', NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (393, 390, '落地页设置', 'PUT', '/system/landingpage', NULL, 1);

INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (434, 390, 'APP下载落地页信息', 'GET', '/system/landingpage/download', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (435, 390, 'APP下载落地页编辑', 'PUT', '/system/landingpage/download', NULL, 1);


#顶部悬浮配置表结构
CREATE TABLE `top_config` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `logo_img` varchar(255) DEFAULT NULL COMMENT 'logo',
      `description` text COMMENT '文字描述',
      `title` varchar(30) DEFAULT NULL COMMENT '标题',
      `url` varchar(255) DEFAULT NULL COMMENT '跳转链接',
      `jump_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '跳转类型 1 app下载落地页 2跳转链接',
      `download` tinyint(1) DEFAULT '0' COMMENT '下载开关 1 开 0 关',
      `commit` varchar(255) DEFAULT NULL  COMMENT '按钮文本',
      `created` datetime DEFAULT NULL,
      `updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='顶部悬浮配置';

#APP首次登录赠送活动
insert into active_template(`id`,`name`,`description`) values(14,'New device APP login','New device APP login');

CREATE TABLE `active_award` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
    `uuid` varchar(255) NOT NULL DEFAULT '' COMMENT '唯一设备号',
    `origin` tinyint(4) DEFAULT '0' COMMENT '来源，1:pc, 2:h5, 3:ios,4:android',
    `active_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user_id` (`user_id`) USING BTREE,
    KEY `uuid` (`uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='活动派奖记录表';