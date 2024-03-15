CREATE TABLE `user_register_deposit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `app_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dev_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_deposit_log_user_id_IDX` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户充值记录';


ALTER TABLE user_register_deposit_log ADD appsflyer_id varchar(50) DEFAULT '' NULL;
