ALTER TABLE `dev_game`.`user_level`
MODIFY COLUMN `lottery_money` bigint(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '投注量' AFTER `icon`,
MODIFY COLUMN `deposit_money` bigint(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '充值金额' AFTER `user_count`;