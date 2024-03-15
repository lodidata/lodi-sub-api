ALTER TABLE `direct_bkge`
    MODIFY COLUMN `bkge_increase` decimal(18, 2) NOT NULL COMMENT '返水提升比例,日',
    ADD COLUMN `bkge_increase_week` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '返水提升比例,周' AFTER `bkge_increase`,
    ADD COLUMN `bkge_increase_month` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '返水提升比例,月' AFTER `bkge_increase_week`;


ALTER TABLE `user_data`
    ADD COLUMN `direct_bkge_increase` decimal(18, 2) NOT NULL DEFAULT 0 NULL COMMENT '返水提升比例,日',
    ADD COLUMN `direct_bkge_increase_week` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '返水提升比例,周' AFTER `direct_bkge_increase`,
    ADD COLUMN `direct_bkge_increase_month` decimal(18, 2) NOT NULL DEFAULT 0 COMMENT '返水提升比例,月' AFTER `direct_bkge_increase_week`;
