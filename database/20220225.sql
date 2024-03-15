#游戏用户加索引
ALTER TABLE `game_user_account` 
ADD UNIQUE INDEX `user_account`(`user_account`) USING BTREE;

ALTER TABLE `game_order_jdb_dz` 
RENAME INDEX `tid` TO `user_id`,
ADD UNIQUE INDEX `order_number`(`seqNo`) USING BTREE;
ALTER TABLE `game_order_jdb_by` 
RENAME INDEX `tid` TO `user_id`,
ADD UNIQUE INDEX `order_number`(`seqNo`) USING BTREE;
ALTER TABLE `game_order_jdb_jj` 
RENAME INDEX `tid` TO `user_id`,
ADD UNIQUE INDEX `order_number`(`seqNo`) USING BTREE;
ALTER TABLE `game_order_jdb_qp` 
RENAME INDEX `tid` TO `user_id`,
ADD UNIQUE INDEX `order_number`(`seqNo`) USING BTREE;

ALTER TABLE `game_order_cqnine_dz` 
DROP INDEX `gametype`,
DROP INDEX `endroundtime`,
ADD UNIQUE INDEX `order_number`(`round`) USING BTREE;

ALTER TABLE `game_order_cqnine_by` 
DROP INDEX `gametype`,
DROP INDEX `endroundtime`,
ADD UNIQUE INDEX `order_number`(`round`) USING BTREE;

ALTER TABLE `game_order_cqnine_jj` 
DROP INDEX `gametype`,
DROP INDEX `endroundtime`,
ADD UNIQUE INDEX `order_number`(`round`) USING BTREE;

ALTER TABLE `game_order_cqnine_qp` 
DROP INDEX `gametype`,
DROP INDEX `endroundtime`,
ADD UNIQUE INDEX `order_number`(`round`) USING BTREE;

ALTER TABLE `game_order_evo` 
ADD INDEX `user_id`(`user_id`) USING BTREE;

#CQ9街机
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (85, 19, 'CQ9JJ', 'CQ9JJ', 'CQ9', 'CQ9街机', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'disabled', NULL, 'disabled', NULL, NULL, NULL, 'disabled');

ALTER TABLE `user_dml` 
ADD COLUMN `CQ9JJ` int(10) UNSIGNED NULL DEFAULT 0 AFTER `CQ9BY`;

ALTER TABLE `orders`
ADD UNIQUE INDEX `order_number_game_type`(`order_number`, `game_type`) USING BTREE;
ALTER TABLE `lottery_order`
ADD INDEX `idx_user_id_updated`(`user_id`, `updated`) USING BTREE;