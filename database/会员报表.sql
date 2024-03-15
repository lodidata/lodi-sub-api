#rpt_user
alter table rpt_user add rebate_withdraw_amount decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '返佣取款金额' after withdrawal_user_cnt;
alter table rpt_user add rebate_withdraw_cnt int(11) NOT NULL DEFAULT '0' COMMENT '返佣取款次数' after rebate_withdraw_amount;