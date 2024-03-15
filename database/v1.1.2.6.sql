#提现审核优化
alter table funds_withdraw change status status enum('canceled','rejected','paid','prepare','pending','failed','refused','confiscate') CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '#状态(rejected:已拒绝, refused:已取消，paid:已支付， prepare:准备支付, pending:待处理，failed：支付失败,canceled:用户取消提款,confiscate:没收)';
alter table user_data add `confiscate_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '没收次数' after withdraw_amount;
alter table user_data add `confiscate_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '没收总额' after confiscate_num;
alter table rpt_deposit_withdrawal_day add `confiscate_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '没收金额' after withdrawal_cnt;
alter table rpt_deposit_withdrawal_day add `confiscate_cnt` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '没收笔数' after confiscate_amount;

#代理后台地址配置
INSERT INTO system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`) VALUES('agent','用户代理后台地址','string','agent_url','',NULL,'enabled');

#消息添加索引
ALTER TABLE `message`
MODIFY COLUMN `admin_name` varchar(255) NULL DEFAULT 'admin' COMMENT '发送人名称' AFTER `admin_uid`,
MODIFY COLUMN `recipient` varchar(255) NOT NULL DEFAULT '' COMMENT '接收人（自定义，会员层级）用户名称 ,号隔开' AFTER `send_type`,
MODIFY COLUMN `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题' AFTER `recipient`,
MODIFY COLUMN `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容' AFTER `title`,
ADD INDEX `idx_created`(`created`) USING BTREE,
ADD INDEX `idx_admin_uid`(`admin_uid`) USING BTREE;


#停用账号的描述
ALTER TABLE `user` ADD COLUMN `forbidden_des` varchar(255) NULL DEFAULT '' COMMENT '停用账号的描述' AFTER `created`;