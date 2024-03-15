#股东分红
ALTER TABLE `funds`
ADD COLUMN `share_balance` int(11) NOT NULL DEFAULT 0 COMMENT '股东分红余额' AFTER `freeze_password`,
ADD COLUMN `share_freeze_withdraw` int(11) NOT NULL DEFAULT 0 COMMENT '股东分红提现冻结金额' AFTER `share_balance`;


#提现记录
ALTER TABLE `funds_withdraw`
ADD COLUMN `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '提现类型,1:主钱包提现,2:股东分红提现' AFTER `user_type`;

#存储过程
p_rpt_deposit_withdrawal_day.sql