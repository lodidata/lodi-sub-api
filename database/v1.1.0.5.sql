#
ALTER TABLE `rpt_lottery_earnlose`
DROP PRIMARY KEY;

#
ALTER TABLE `rpt_userlottery_earnlose`
DROP PRIMARY KEY;

#
ALTER TABLE `orders`
ADD COLUMN `order_time` timestamp NULL COMMENT '下注时间' AFTER `date`;

#
ALTER TABLE `rpt_agent`
ADD COLUMN `new_register_deposit_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增充值金额(新注册用户总充值的金额 )' AFTER `balance_amount`,
ADD COLUMN `new_register_deposit_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新注册用户充值人数' AFTER `new_register_deposit_amount`,
ADD COLUMN `deposit_user_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总充值人数' AFTER `new_register_deposit_num`;
#
ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `new_register_deposit_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增充值金额(新注册用户总充值的金额 )' AFTER `back_cnt`,
ADD COLUMN `deposit_user_num` int(11) UNSIGNED NOT NULL  DEFAULT 0 COMMENT '总充值人数' AFTER `new_register_deposit_amount`,
ADD COLUMN `new_deposit_user_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '首充人数' AFTER `deposit_user_num`,
ADD COLUMN `new_register_deposit_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新注册的玩家且有充值人数' AFTER `new_deposit_user_num`,
ADD COLUMN `new_user_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新用户数' AFTER `new_deposit_user_num`;

#
ALTER EVENT `e_1`
ON SCHEDULE
EVERY '3' MINUTE STARTS '2021-11-16 11:45:06';
