ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;

ALTER TABLE `agent_loseearn_week_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_week_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;

ALTER TABLE `agent_loseearn_month_bkge`
ADD COLUMN `sub_loseearn_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '直属下级盈亏' AFTER `loseearn_amount`;
ALTER TABLE `agent_loseearn_month_bkge`
ADD COLUMN `sub_loseearn_amount_list` json NOT NULL COMMENT '直属下级盈亏列表' AFTER `loseearn_amount_list`;