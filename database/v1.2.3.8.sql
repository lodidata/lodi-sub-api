#月俸禄优化
alter table user_level add `monthly_recharge` int(11) NOT NULL DEFAULT '0' COMMENT '月俸禄充值条件，分为单位' after monthly_percent;

#提现审核优化
ALTER TABLE funds_withdraw ADD bank_id INT(11) NOT NULL DEFAULT '0' COMMENT '银行表ID' AFTER receive_bank_account_id;
ALTER TABLE funds_withdraw CHANGE `status` `status` ENUM('canceled','rejected','paid','prepare','pending','failed','refused','confiscate','obligation','lock') CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '#状态(rejected:已拒绝, refused:已取消，paid:已支付， prepare:准备支付, pending:待处理，failed：支付失败,canceled:用户取消提款,confiscate:没收,obligation:待付款,lock:锁定)';
