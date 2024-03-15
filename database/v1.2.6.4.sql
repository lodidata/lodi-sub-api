#活动列表-活动用户申请限制用户
CREATE TABLE `active_apply_blacklist` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL COMMENT '用户ID',
      `user_name` varchar(255) DEFAULT NULL COMMENT '用户名',
      `active_id` int(11) NOT NULL COMMENT '活动ID',
      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
      `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
      PRIMARY KEY (`id`),
      KEY `Uid_ActiveId` (`user_id`,`active_id`)
) ENGINE=InnoDB COMMENT='活动用户申请限制用户';

alter table active add blacklist_url varchar(200)  COMMENT '限制名单csv地址';