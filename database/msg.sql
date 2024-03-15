#新增字段
ALTER TABLE `rebet_log`
    ADD COLUMN `batch_no` int(11) NOT NULL DEFAULT 0 COMMENT '批次号' AFTER `desc`;

ALTER TABLE `rebet_log`
    ADD COLUMN `active_apply_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动申请表id' AFTER `desc`;

ALTER TABLE `rebet`
    ADD COLUMN `proportion_value` int(11) NOT NULL DEFAULT 0 COMMENT '返水比例' AFTER `day`;
#添加索引
ALTER TABLE `rebet_log`
    ADD INDEX  Idx_apid_uid (active_apply_id,user_id) ;

