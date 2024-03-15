CREATE TABLE `community_bbs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '社区名字',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标地址',
  `jump_url` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否状态 0是 1否',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='社区论坛交流表\r\n@author: stanley';

insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`,`updated_at`) values('system','社区论坛总开关','bool','community_bbs','1','1开，0关','enabled','2022-05-31 15:34:13');
insert into admin_user_role_auth(`id`,`pid`,`name`,`status`) values(274,123,'社群论坛管理',1);
insert into admin_user_role_auth(`id`,`pid`,`name`,`method`,`path`,`status`) values(275,274,'查询列表','GET','/community/list',1),(276,274,'添加社区','POST','/community/add',1),(277,274,'查询单个社区','GET','/community/edit',1),(278,274,'编辑社区','PUT','/community/edit',1),(279,274,'删除社区','POST','/community/del',1);


CREATE TABLE `user_agent_link_state`  (
                                          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                          `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
                                          `link_key` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '推广链接对应的key',
                                          `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '推广链接',
                                          `link_module` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '推广链接类型：market, ...',
                                          `link_id` int(11) NOT NULL DEFAULT 0 COMMENT '推广链接对应system_config表中的id',
                                          `use_state` int(11) NOT NULL DEFAULT 0 COMMENT '用户对链接的启用状态：1启用中，0关闭中',
                                          `is_del` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否管理端对其删除：1-是，0-否',
                                          `is_proxy` tinyint(4) NOT NULL DEFAULT 1 COMMENT '是否允许代理：1-是，0-否',
                                          PRIMARY KEY (`id`) USING BTREE,
                                          INDEX `uid`(`uid`) USING BTREE,
                                          INDEX `link`(`link`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户的推广链接启用情况' ROW_FORMAT = Dynamic;


#转入转出失败之前数据不处理，可能手动处理过
update `game_money_error` set status=1 where status=0;