#后台返佣历史路由
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (264, 183, '返佣列表', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth` (`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (265, 264, '信息查询', 'GET', '/active/newBkge/list', NULL, 1);

CREATE TABLE `agent_code`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) UNSIGNED NOT NULL COMMENT '代理id',
  `code` varchar(15) NOT NULL COMMENT '代理邀请码',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `udx_code`(`code`) USING BTREE
);
ALTER TABLE `agent_code`
ADD INDEX `idx_user_id`(`agent_id`) USING BTREE;