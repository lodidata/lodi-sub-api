CREATE TABLE `active_sign_up`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `active_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动id',
  `apply_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '申请时间',
  `first_deposit_time` timestamp NULL COMMENT '第一次充值时间',
  `second_deposit_time` timestamp NULL COMMENT '第二次充值时间',
  `third_deposit_time` timestamp NULL COMMENT '第三次充值时间',
  `times` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参与次数',
  `can_play_all_game` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1:可以玩所有游戏，0：只能玩电子游戏',
  `apply_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参与状态（0：不参与，1：参与）',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) COMMENT = '用户申请参与活动';
ALTER TABLE `dev_game`.`active_sign_up` 