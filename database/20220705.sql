
ALTER TABLE `game_money_error` ADD COLUMN `transfer_type` enum('in','out') NOT NULL DEFAULT 'in' COMMENT '类型 in转入 out转出' AFTER `status`;

INSERT INTO `system_config` (`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (124, 'rakeBack', '是否开启三级返佣', 'bool', 'bkge_open_third', '0', '1开，0关', 'enabled', '2022-07-05 16:43:00');