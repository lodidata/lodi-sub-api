CREATE TABLE `channel_package` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(20) NOT NULL COMMENT '母包名称',
`url` varchar(100) NOT NULL COMMENT '母包地址',
`channel_id` varchar(20) NOT NULL DEFAULT '' COMMENT '渠道号',
`download_url` varchar(100) NOT NULL DEFAULT '' COMMENT '分包下载地址',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '生成状态,0:未生成,1:已生成,2:生成中,3:已删除',
`batch_no` int(11) DEFAULT NULL COMMENT '批次号',
`create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX `idx_batch_no`(`batch_no`)
) ENGINE=InnoDB COMMENT='渠道代理包生成表';



INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (440, 343, '渠道代理包生成', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (441, 440, '渠道代理包列表', 'GET', '/channel/package', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (442, 440, '渠道代理包添加且生成', 'POST', '/channel/package', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (443, 440, '渠道代理包生成', 'PUT', '/channel/package', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (444, 440, '渠道代理包删除', 'DELETE', '/channel/package', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (445, 440, '渠道列表', 'GET', '/channel/package/list', NULL, 1);

