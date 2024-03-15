#删除代理退佣活动
truncate active_bkge;

ALTER TABLE `active_bkge`
ADD COLUMN `new_bkge_set` json NULL COMMENT '新代理退佣设置(deposit_withdraw_fee_ratio:充值提款手续费比例，winloss_fee_ratio:网站输赢费用比例，valid_user_num:最低有效用户数，valid_user_deposit:有效用户充值，valid_user_bet:有效用户下注,bkge_ratio_rule:退佣比例规则(min_winloss,max_winloss,bkge_scale))' AFTER `condition_opt`,
ADD COLUMN `desc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '代理说明' AFTER `data_opt`;

ALTER TABLE `rpt_user`
ADD COLUMN `first_deposit` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1:首充，0:不是首充' AFTER `promotion_user_winnings`;

ALTER TABLE `rpt_user`
ADD INDEX `idx_user_id`(`user_id`) USING BTREE;