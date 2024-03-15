ALTER TABLE `unlimited_agent_bkge`
ADD COLUMN `self_bkge` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '自身返佣金额' AFTER `bkge`,
ADD COLUMN `self_bet_amount` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '自身投注额' AFTER `bet_amount`,
ADD COLUMN `self_bet_amount_list` json NOT NULL COMMENT '自身游戏投注列表' AFTER `bet_amount_list`,
ADD COLUMN `self_bkge_list` json NOT NULL COMMENT '自身游戏返佣列表' AFTER `bkge_list`,
ADD COLUMN `self_proportion_list` json NOT NULL COMMENT '自身游戏占成列表' AFTER `proportion_list`;