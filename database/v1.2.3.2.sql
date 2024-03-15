#增加渠道号字段
ALTER TABLE `user`
    ADD COLUMN `channel_id` varchar(50) NULL DEFAULT NULL COMMENT '渠道号' AFTER `invit_code`,
    ADD INDEX `idx_channel_id`(`channel_id`) USING BTREE;

#渠道管理
CREATE TABLE `channel_management` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道编号',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道名称',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道地址',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_number` (`number`) USING BTREE,
  INDEX `idx_channel_name`(`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT = 1  CHARSET=utf8mb4 COMMENT='渠道管理表';


#渠道管理-下载配置
CREATE TABLE `channel_download`  (
     `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `channel_no` varchar(100) NOT NULL DEFAULT '' COMMENT '渠道号',
     `channel_name` varchar(255) NOT NULL COMMENT '渠道名称',
     `product_name` varchar(100) NOT NULL DEFAULT '' COMMENT '产品包名称',
     `download_url` varchar(255) NOT NULL DEFAULT '' COMMENT '下载链接',
     `H5_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'H5推广地址',
     `android` varchar(255) NOT NULL DEFAULT '' COMMENT '安卓包',
     `super_label` varchar(255) NOT NULL DEFAULT '' COMMENT '超级签',
     `super_label_state` tinyint(4) NOT NULL DEFAULT 1 COMMENT '超级签启用状态：1-启用，0-未启用',
     `enterprise_label` varchar(255) NOT NULL DEFAULT '' COMMENT '企业签',
     `enterprise_label_state` tinyint(4) NOT NULL DEFAULT 1 COMMENT '企业签启用状态：1-启用，0-未启用',
     `TF_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'TF签',
     `TF_label_state` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'TF签启用状态：1-启用，0-未启用',
     `icon_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'icon地址',
     `bottom_text` varchar(500)  NOT NULL COMMENT '底部文字',
     `is_delete` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否删除:1-已经删除，0-未删除',
     `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
     `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
     PRIMARY KEY (`id`) USING BTREE,
     INDEX `idx_channel_number`(`channel_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COMMENT = '渠道管理-下载页配置';


#渠道管理权限菜单
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`sort`,`status`) 
values
(343,0,'渠道管理',null,null,null,1),
(344,343,'渠道列表',null,null,null,1),
(345,344,'渠道列表查询','GET','/channel',1,1),
(346,344,'渠道详情','GET','/channel/edit',2,1),
(347,344,'渠道编辑','PUT','/channel/edit',3,1),
(348, 343, '下载页配置', NULL, NULL, NULL, 1),
(349, 348, '获取下载页配置', 'GET', '/channel/download', NULL, 1),
(350, 348, '新增下载页配置', 'POST', '/channel/download', NULL, 1),
(351, 348, '编辑下载页配置', 'PUT', '/channel/download', NULL, 1),
(352, 348, '删除下载页配置', 'DELETE', '/channel/download', NULL, 1),
(353, 343, '渠道报表汇总', NULL, NULL, NULL, 1),
(354, 353, '获取报表', 'GET', '/channel/report', NULL, 1),
(355, 353, '导出报表', 'GET', '/channel/report/export', NULL, 1);
