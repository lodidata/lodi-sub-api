ALTER TABLE `rpt_deposit_withdrawal_day`
ADD COLUMN `new_register_withdraw_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增取款金额' AFTER `new_register_deposit_amount`;

ALTER TABLE `admin_index_third`
ADD COLUMN `new_register_withdraw_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '新增充值金额' AFTER `recharge_first_avg`;