ALTER TABLE `rpt_user`
ADD COLUMN `dml` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '用户打码量' AFTER `bet_user_amount`;

ALTER TABLE `admin_index_third`
ADD COLUMN `new_deposit_retention` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '首充次日付费留存' AFTER `recharge_first_avg`,
ADD COLUMN `new_deposit_bet_retention` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '首充次日活跃留存' AFTER `new_deposit_retention`,
ADD COLUMN `no_agent_user_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主渠道新增注册(注册时无上级代理)' AFTER `new_register_withdraw_amount`,
ADD COLUMN `deposit_user_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总充值人数' AFTER `no_agent_user_num`,
ADD COLUMN `bet_today_kill_rate` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '流水杀率' AFTER `deposit_user_num`,
ADD COLUMN `revenue_today_kill_rate` decimal(6, 2) NOT NULL DEFAULT 0.00 COMMENT '营收杀率' AFTER `bet_today_kill_rate`,
ADD COLUMN `old_user_deposit_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '老用户充值人数' AFTER `revenue_today_kill_rate`,
ADD COLUMN `old_user_deposit_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '老用户充值金额' AFTER `old_user_deposit_num`,
ADD COLUMN `old_user_deposit_avg` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '老用户平均充值金额' AFTER `old_user_deposit_amount`,
ADD COLUMN `new_deposit_user_dml` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '新充打码量' AFTER `old_user_deposit_avg`,
CHANGE COLUMN `witchdraw_total` `withdraw_total` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '总兑换' AFTER `recharge_total`;