#日流水不能为0
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '日流水不能为0', 'json', 'day_gt_zero', '{}', NULL, 'enabled');

#周流水不能为0
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '周流水不能为0', 'bool', 'week_gt_zero', '0', NULL, 'enabled');

#月流水不能为0
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'rebet_config', '月流水不能为0', 'bool', 'month_gt_zero', '0', NULL, 'enabled');

#充值用户参与返佣
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`) VALUES ( 'profit_loss', '充值用户参与返佣', 'bool', 'must_has_recharge', '0', '充值用户参与返佣', 'enabled');

CREATE TABLE `rebet_deduct` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
    `batch_no` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '批次号',
    `rebet` bigint(20) NOT NULL DEFAULT '0' COMMENT '返水金额，分',
    `deduct_rebet` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '暗扣金额，分',
    `order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号',
    `type` int(10) unsigned NOT NULL DEFAULT '701' COMMENT '类型 701：日返 702：周返 703：月返',
    `deposit_amount` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '充值金额，分',
    `coupon_amount` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '彩金，分',
    PRIMARY KEY (`id`),
    KEY `idx_user_order_number` (`user_id`,`order_number`),
    KEY `idx_batch_user` (`batch_no`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='暗扣详情';

# redis
# del system.config.global.key