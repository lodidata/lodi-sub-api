ALTER TABLE `active` 
MODIFY COLUMN `condition_recharge` smallint(6) NULL DEFAULT NULL COMMENT '申请条件--是否有充值记录 0没有 1有 2当天有 2本周有 3当月有' AFTER `apply_times`;

ALTER TABLE `active_template` 
ADD COLUMN `count_type` smallint(6) NOT NULL DEFAULT 1 COMMENT '可配置的活动数量类型 1限制一个 2不限制 3同条件下一个' AFTER `state`;

#盈亏返佣提现限额
INSERT INTO `system_config`(`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (268, 'withdraw', '盈亏返佣提现限额', 'json', 'withdraw_bkge_money', '{\"withdraw_min\":0,\"withdraw_max\":999999900}', 'json值', 'enabled', '2023-03-15 14:56:06');