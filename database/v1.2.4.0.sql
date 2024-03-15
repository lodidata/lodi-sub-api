#公告排序
alter table notice add `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序' after status;

ALTER TABLE `pay_callback`
DROP INDEX `idx_pay_date`;

ALTER TABLE `pay_callback`
ADD INDEX `idx_created_pay`(`created`, `pay_type`) USING BTREE;

#支付通道
ALTER TABLE `pay_channel`
ADD COLUMN `give_recharge_dml` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '充值赠送打码量' AFTER `give_dml`,
ADD COLUMN `give_lottery_dml` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '彩金赠送打码量' AFTER `give_recharge_dml`;

ALTER TABLE `payment_channel`
ADD COLUMN `give_recharge_dml` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '充值赠送打码量' AFTER `give_dml`,
ADD COLUMN `give_lottery_dml` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '彩金赠送打码量' AFTER `give_recharge_dml`;


ALTER TABLE `active_rule`
MODIFY COLUMN `give_condition` int(11) NULL DEFAULT NULL COMMENT '赠送条件,1:单日首笔,2:单日累计,3:周累计,4:月累计,5:自定义(充值活动为单日单笔)' AFTER `give_time`;


#初始化充值活动周累计发送时间
update active_rule set issue_day=1,issue_time='04:00:00' where template_id = 11 AND give_condition = 3;