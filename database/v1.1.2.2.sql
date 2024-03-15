#++++++++++lodi_super_admin

ALTER TABLE `game_order_cq9` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;

#JILI添加真人游戏

INSERT INTO `game_menu` (`pid`, `type`, `name`, `alias`, `rename`,`status`, `switch`, `across_status`) VALUES (15, 'JILILIVE', 'JILICasino', 'JILI', 'JILI真人', 'enabled', 'enabled', 'enabled');

INSERT INTO game_3th ( kind_id, game_id, game_name, `rename`, alias, type, game_img )
VALUES
	( 118, 101, 'Big Small', '大小競猜', 'BigSmall', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/118.png' ),
	( 111, 101, 'Number King', '數字之王', 'NumberKing', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/111.png' ),
	( 112, 101, 'Journey West M', '西遊爭霸M', 'JourneyWestM', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/112.png' ),
	( 113, 101, 'Poker King', '撲克之王', 'PokerKing', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/113.png' ),
	( 123, 101, 'Dragon & Tiger', '龍虎鬥', 'DragonTiger', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/123.png' ),
	( 122, 101, 'iRich Bingo', 'iRich Bingo', 'iRichBingo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/122.png' ),
	( 124, 101, '7up7down', '7上7下', '7up7down', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/124.png' ),
	( 152, 101, 'Baccarat', '百家樂', 'Baccarat', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/152.png' ),
	( 139, 101, 'Fortune Bingo', 'Fortune Bingo', 'FortuneBingo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/139.png' ),
	( 125, 101, 'Sic Bo', '骰寶', 'SicBo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/125.png' ),
	(22039,93,'ROBIN HOOD','罗宾汉','ROBINHOOD','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22039.png'),
	(22040,93,'LUCKY FORTUNES','财富连连','LUCKYFORTUNES','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22040.png'),
	(22041,93,'CHINESE NEW YEAR 2','大过年2','CHINESENEWYEAR2','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22041.png');
	
	



#+++++++++++++++++++lodi子站


#添加支付回调域名配置,默认为空
alter table pay_config add pay_type text COMMENT '支付方式';
ALTER TABLE `pay_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成支付回调域名' AFTER `pay_type`;
ALTER TABLE pay_config CHANGE `key` `key` VARCHAR(1700) NOT NULL COMMENT '私钥';

#添加代付回调域名配置
ALTER TABLE `transfer_config`  ADD COLUMN `pay_callback_domain` varchar(100) NULL DEFAULT '' COMMENT '生成代付回调域名' AFTER `request_url`;

ALTER TABLE `transfer_log` MODIFY COLUMN `response` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `json`;

#支付，代付日志
ALTER TABLE `pay_request_third_log` ADD COLUMN `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付类型代码' AFTER `id`;

ALTER TABLE `transfer_log` ADD COLUMN `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式' AFTER `order_id`, ADD COLUMN `payUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '请示接口地址' AFTER `pay_type`;


ALTER TABLE `game_money_error` ADD COLUMN `transfer_type` enum('in','out') NOT NULL DEFAULT 'in' COMMENT '类型 in转入 out转出' AFTER `status`;

INSERT INTO `system_config` (`id`, `module`, `name`, `type`, `key`, `value`, `desc`, `state`, `updated_at`) VALUES (124, 'rakeBack', '是否开启三级返佣', 'bool', 'bkge_open_third', '0', '1开，0关', 'enabled', '2022-07-05 16:43:00');

#客服代码
INSERT INTO `system_config` (`module`, `name`, `type`, `key`) VALUES ('system', '客服代码', 'string', 'kefu_code');




#CQ9

ALTER TABLE `game_order_cq9` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;

ALTER TABLE `game_order_cqnine_by` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;
ALTER TABLE `game_order_cqnine_dz` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;
ALTER TABLE `game_order_cqnine_jj` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;
ALTER TABLE `game_order_cqnine_qp` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `updated`;
ALTER TABLE `game_order_cqnine_table` ADD COLUMN `currency` char(3) NOT NULL DEFAULT 'PHP' COMMENT '货币' AFTER `roundnumber`;




#JILI添加真人游戏
ALTER TABLE `user_dml` ADD COLUMN `JILILIVE` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `JILIBY`;

INSERT INTO `game_menu` (`pid`, `type`, `name`, `alias`, `rename`,`status`, `switch`, `across_status`) VALUES (15, 'JILILIVE', 'JILICasino', 'JILI', 'JILI真人', 'disabled', 'enabled', 'enabled');

INSERT INTO game_3th ( kind_id, game_id, game_name, `rename`, alias, type, game_img )
VALUES
	( 118, 101, 'Big Small', '大小競猜', 'BigSmall', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/118.png' ),
	( 111, 101, 'Number King', '數字之王', 'NumberKing', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/111.png' ),
	( 112, 101, 'Journey West M', '西遊爭霸M', 'JourneyWestM', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/112.png' ),
	( 113, 101, 'Poker King', '撲克之王', 'PokerKing', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/113.png' ),
	( 123, 101, 'Dragon & Tiger', '龍虎鬥', 'DragonTiger', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/123.png' ),
	( 122, 101, 'iRich Bingo', 'iRich Bingo', 'iRichBingo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/122.png' ),
	( 124, 101, '7up7down', '7上7下', '7up7down', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/124.png' ),
	( 152, 101, 'Baccarat', '百家樂', 'Baccarat', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/152.png' ),
	( 139, 101, 'Fortune Bingo', 'Fortune Bingo', 'FortuneBingo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/139.png' ),
	( 125, 101, 'Sic Bo', '骰寶', 'SicBo', 'Casion', 'https://update.lodigame.com/lodi/game/vert/jili/125.png' ),
	(22039,93,'ROBIN HOOD','罗宾汉','ROBINHOOD','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22039.png'),
	(22040,93,'LUCKY FORTUNES','财富连连','LUCKYFORTUNES','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22040.png'),
	(22041,93,'CHINESE NEW YEAR 2','大过年2','CHINESENEWYEAR2','slot', 'https://update.lodigame.com/lodi/game/vert/fc/22041.png');
	
	
	
	

#++++++++++++++bet77 设置支付回调地址
update pay_config set pay_callback_domain='http://api-www.afrumc.com' where type='yypay';

update transfer_config set pay_callback_domain='http://api-admin.bet77.lol' where code='TGPAY';




	
	
#+++++++++++redis lodi子站
del menu:vertical:list
del menu:vertical:list:93
1、del game_jump_url
2、del system.config.global.key
3、del pay_config_list



#++++++++++++++++++++lodi子站重启进程 

messsageServer 
gameOrderServer
gameServer