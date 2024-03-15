#增加斗鸡分类
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`, `qp_img`, `qp_img2`, `img`, `qp_icon`, `qp_un_icon`, `list_mode`, `quit`, `sort`, `hot_sort`, `status`, `hot_status`, `update_at`, `switch`, `m_start_time`, `m_end_time`, `across_sort`, `across_status`) VALUES (24, 0, 'SABONG', 'SABONG', 'SABONG', '斗鸡', NULL, NULL, '', NULL, NULL, '1', '1', 10, 0, 'enabled', 'disabled', NULL, 'enabled', NULL, NULL, NULL, 'enabled');
#把sv388放到斗鸡里
update game_menu set pid = 24 where type='SV388';

#系统设置加上sabong
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('rakeBack', '斗鸡返佣', 'int', 'SABONG', '10', NULL, 'enabled', NULL);
INSERT INTO `system_config` (`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ('audit', '斗鸡稽核', 'int', 'SABONG', '100', NULL, 'enabled', '2022-09-06 16:17:05');
