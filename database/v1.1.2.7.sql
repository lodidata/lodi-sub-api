#首页全部游戏排序 lodi
INSERT INTO `system_config`(`module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'game', '首页全部游戏', 'string', 'all_game', 'JILI,FC,CQ9,SEXYBCRT,AT,CG,BNG,SA,KMQM,AVIA,UG', NULL, 'enabled', NULL);

#首页全部游戏排序 ncg
INSERT INTO `system_config`( `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES ( 'game', '首页全部游戏', 'string', 'all_game', 'PG,JILI,JOKER,PP,JDB,CQ9,SEXYBCRT,PNG,KMQM,SBO,TF,EVO', NULL, 'enabled', NULL);


#推广活动
alter table active_rule add `send_bet_max` int(11) DEFAULT NULL COMMENT '最大打码量' after send_max;

#菲版
insert into active_template(`name`,`description`,`state`,`created_uid`,`created`,`updated`) values('promotional activities','promotional activities','',null,'2022-08-04 17:00:00','2022-08-04 17:00:00');

#泰版
insert into active_template(`name`,`description`,`state`,`created_uid`,`created`,`updated`) values('กิจกรรมส่งเสริมการขาย','กิจกรรมส่งเสริมการขาย','',null,'2022-08-04 17:00:00','2022-08-04 17:00:00');