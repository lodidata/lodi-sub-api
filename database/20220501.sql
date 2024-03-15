#注册IP限制人数

INSERT INTO `system_config` (`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (95, 'system', '注册IP限制人数', 'int', 'register_limit_ip_count', '2', NULL, 'enabled', NULL);

#删除redis
#del system.config.global.key