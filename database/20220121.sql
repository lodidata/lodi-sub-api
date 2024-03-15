ALTER TABLE `dev_game`.`rebet`
MODIFY COLUMN `day` date NULL DEFAULT NULL COMMENT '日期' AFTER `d_percent`;

ALTER TABLE `dev_game`.`bkge`
MODIFY COLUMN `day` date NULL DEFAULT NULL COMMENT '日期' AFTER `cur_bkge`;