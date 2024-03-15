ALTER TABLE `active_backwater`
MODIFY COLUMN `type` tinyint(3) NULL DEFAULT NULL COMMENT '批次类型,1:日返水,2:周返水,3:月返水,4:月俸禄' AFTER `batch_no`,
ADD COLUMN `active_type` int(11) NULL DEFAULT 1 COMMENT '返水类型,1:活动返水,2:会员等级' AFTER `type`;


ALTER TABLE `rebet`
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 1 COMMENT '状态 0不成功金额为0，1:未发放,2:未领取,3:已领取' AFTER `type`,
ADD COLUMN `batch_no` int(11) NULL COMMENT '批次号' AFTER `plat_id`,
ADD COLUMN `dml_amount` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '打码量' AFTER `batch_no`,
ADD COLUMN `process_time` timestamp(0) NULL DEFAULT NULL COMMENT '领取时间' AFTER `dml_amount`,
ADD INDEX `idx_batch_no`(`batch_no`) USING BTREE;



ALTER TABLE `user_monthly_award`
ADD COLUMN `status` tinyint(4) NULL COMMENT '状态 1:未发放,2:未领取,3:已领取' AFTER `updated`,
ADD COLUMN `batch_no` int(11) NULL COMMENT '批次号' AFTER `status`,
ADD COLUMN `dml_amount` int(11) NULL DEFAULT 0 COMMENT '打码量' AFTER `batch_no`,
ADD COLUMN `process_time` timestamp(0) NULL COMMENT '领取时间' AFTER `dml_amount`,
ADD INDEX `idx_batch_no`(`batch_no`) USING BTREE;


p_rpt_channel 存储过程