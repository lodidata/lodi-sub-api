
#JDB街机
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (97, 19, 'JDBJJ', 'JDBARCADE', 'JDB', 'JDB街机', NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, 'enabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');

#JDB街机打码量
ALTER TABLE `user_dml` 
ADD COLUMN `JDBJJ` int(10) UNSIGNED NULL DEFAULT 0 AFTER `JDBQP`;