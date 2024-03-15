#用户登录添加版本信息
ALTER TABLE `user_logs` ADD COLUMN `version` varchar(50) NULL DEFAULT NULL COMMENT '版本信息' AFTER `log_ip`;

#活动模板
INSERT INTO `active_template`(`id`, `name`, `description`, `created`, `updated`) VALUES (18, 'App top-up gift', 'App top-up gift', '2023-06-26 15:28:01', '2023-06-26 15:28:04')


