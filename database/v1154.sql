ALTER TABLE `user`
    ADD COLUMN `is_verify` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信验证状态,0:未验证,1:已验证';

update `user` set is_verify=1;


INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'register', '注册验证码开关', 'bool', 'register_verify_switch', '1', '1开，0关', 'enabled', '2023-04-19 14:53:56');
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('withdraw', '提现验证码开关', 'bool', 'withdraw_verify_switch', '0', '1开，0关', 'enabled', '2023-04-19 14:55:04');

del system.config.global.key
