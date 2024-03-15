ALTER TABLE `active_apply`
    ADD COLUMN `apply_count_status` tinyint(4) NULL DEFAULT 1 COMMENT '是否算入当日申请次数计算(1算入 0不算入)' AFTER `reason`;