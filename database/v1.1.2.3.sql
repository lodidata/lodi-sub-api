#系统设置sql
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'system', 'ip开关设置', 'bool', 'register_limit_ip_switch', '0', '1开，0关', 'enabled', NULL);
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('system', 'ip地址限制设置', 'string', 'register_limit_ip_list', '', NULL, 'enabled', '2022-07-13 16:37:29');

#菜单id
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (294, 123, 'ip设置', NULL, NULL, NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (295, 294, '查询', 'GET', '/ip/register', NULL, 1);
INSERT INTO `admin_user_role_auth`(`id`, `pid`, `name`, `method`, `path`, `sort`, `status`) VALUES (296, 294, '编辑', 'PUT', '/ip/register', NULL, 1);


